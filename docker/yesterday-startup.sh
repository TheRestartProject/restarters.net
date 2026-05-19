#!/usr/bin/env bash

# Do NOT use set -e — we must always reach supervisord at the end.

LOG=/var/log/yesterday-restore.log

log() { echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): $*" | tee -a "$LOG"; }

# Ensure storage directories exist
mkdir -p /var/www/storage/framework/{sessions,views,cache/data}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache
mkdir -p /var/log/nginx
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Remove backup cron — yesterday mode only restores, never creates backups
( crontab -l 2>/dev/null | grep -v 'db-backup' ) | crontab -

# Set up auth gate (same as production)
if [ "${BASIC_AUTH_ENABLED:-}" = "true" ]; then
    EXPECTED=$(php -r "echo hash_hmac('sha256', getenv('BASIC_AUTH_PASSWORD') ?: 'yesterday', getenv('APP_KEY') ?: 'fallback');")
    printf 'map $cookie_site_auth $auth_gate_valid {\n    "%s" 1;\n    default 0;\n}\nmap $auth_gate_valid $auth_check_status {\n    1 200;\n    default 401;\n}\n' \
        "$EXPECTED" > /etc/nginx/auth-gate-map.conf
    printf 'auth_request /_auth_check;\nerror_page 401 = @auth_gate;\n' \
        > /etc/nginx/auth-gate.conf
else
    printf 'map $cookie_site_auth $auth_gate_valid { default 1; }\nmap $auth_gate_valid $auth_check_status { default 200; }\n' \
        > /etc/nginx/auth-gate-map.conf
    : > /etc/nginx/auth-gate.conf
fi

# Substitute environment variables in nginx config for Tigris proxy
if [ -n "$AWS_BUCKET" ]; then
    export TIGRIS_BUCKET_URL="https://${AWS_BUCKET}.fly.storage.tigris.dev"
    export TIGRIS_BUCKET_HOST="${AWS_BUCKET}.fly.storage.tigris.dev"
    envsubst '${TIGRIS_BUCKET_URL} ${TIGRIS_BUCKET_HOST}' < /etc/nginx/nginx.conf > /etc/nginx/nginx.conf.tmp
    mv /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf
fi

# ── Start local MariaDB ───────────────────────────────────────────────────────

log "Starting local MariaDB..."
mkdir -p /var/lib/mysql /run/mysqld
chown -R mysql:mysql /var/lib/mysql /run/mysqld

if [ ! -d /var/lib/mysql/mysql ]; then
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

mysqld_safe --user=mysql --skip-networking=0 --bind-address=127.0.0.1 \
    --innodb-buffer-pool-size=1G \
    --innodb-flush-log-at-trx-commit=2 &

# Wait for MariaDB (up to 60s)
for i in $(seq 1 30); do
    if mysqladmin ping --silent 2>/dev/null; then break; fi
    sleep 2
done

# Create database and app user
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE:-restarters}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-restarters}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD:-restarters}';" 2>/dev/null
mysql -e "GRANT ALL ON \`${DB_DATABASE:-restarters}\`.* TO '${DB_USERNAME:-restarters}'@'127.0.0.1'; FLUSH PRIVILEGES;" 2>/dev/null

log "MariaDB ready."

# ── Restore backup (synchronous — supervisord starts after restore completes) ─
# The health check grace_period (1200s) covers the full restore time.
# Running synchronously ensures the machine never auto-stops mid-restore.

YESTERDAY=$(date -u -d 'yesterday' +%Y%m%d)
RCLONE_FLAGS="--drive-root-folder-id=$GDRIVE_BACKUP_FOLDER_ID --drive-team-drive=$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE"

list_backups() {
    rclone lsf "gdrive:" $RCLONE_FLAGS 2>/dev/null | grep 'db-backup-.*\.sql\.gz' | sort
}

log "Finding backup to restore..."
BACKUP_FILE=$(list_backups | grep "db-backup-${YESTERDAY}-03" | head -1)

if [ -z "$BACKUP_FILE" ]; then
    log "No 3am backup for ${YESTERDAY}, trying any backup from yesterday..."
    BACKUP_FILE=$(list_backups | grep "db-backup-${YESTERDAY}-" | head -1)
fi

if [ -z "$BACKUP_FILE" ]; then
    log "No yesterday backup found, falling back to oldest available..."
    BACKUP_FILE=$(list_backups | head -1)
fi

if [ -z "$BACKUP_FILE" ]; then
    log "ERROR: No backup files found in Google Drive. Starting with empty database."
else
    log "Selected: $BACKUP_FILE"
    log "Downloading (~100MB)..."

    if rclone copy "gdrive:$BACKUP_FILE" /tmp/ $RCLONE_FLAGS 2>>"$LOG"; then
        log "Download complete. Restoring database..."

        if gunzip -c "/tmp/$BACKUP_FILE" | mysql --protocol=TCP -h 127.0.0.1 \
            -u root \
            "${DB_DATABASE:-restarters}" 2>>"$LOG"; then

            log "Database restore complete."
            RAW=$(echo "$BACKUP_FILE" | grep -oE '[0-9]{8}-[0-9]{6}')
            SNAPSHOT_TIME=$(echo "$RAW" | sed 's/\([0-9]\{4\}\)\([0-9]\{2\}\)\([0-9]\{2\}\)-\([0-9]\{2\}\)\([0-9]\{2\}\)\([0-9]\{2\}\)/\1-\2-\3 \4:\5:\6/')
            echo "${SNAPSHOT_TIME} UTC" > /var/www/storage/framework/yesterday-snapshot.txt
            chown www-data:www-data /var/www/storage/framework/yesterday-snapshot.txt
            log "Snapshot timestamp: ${SNAPSHOT_TIME} UTC"
        else
            log "ERROR: Database restore failed."
        fi

        rm -f "/tmp/$BACKUP_FILE"
    else
        log "ERROR: Download failed."
    fi
fi

# Warm up Laravel caches after restore
php /var/www/artisan config:cache 2>/dev/null || true
php /var/www/artisan route:cache 2>/dev/null || true
php /var/www/artisan view:cache 2>/dev/null || true
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
log "Laravel caches warmed. Starting supervisord..."

# ── Start supervisord — health check passes only after restore is complete ────
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
