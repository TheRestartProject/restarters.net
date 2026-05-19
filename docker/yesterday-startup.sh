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
    EXPECTED=$(php -r "echo hash_hmac('sha256', getenv('BASIC_AUTH_PASSWORD') ?: 'project', getenv('APP_KEY') ?: 'fallback');")
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

# ── Restore backup ────────────────────────────────────────────────────────────

log "Starting yesterday restore..."

# Find backup: prefer yesterday's 3am UTC (= UK 4am BST / 3am GMT).
# Falls back to any yesterday backup, then oldest available.
YESTERDAY=$(date -u -d 'yesterday' +%Y%m%d)

RCLONE_COMMON_FLAGS="--drive-root-folder-id=$GDRIVE_BACKUP_FOLDER_ID --drive-team-drive=$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE"

list_backups() {
    rclone lsf "gdrive:" $RCLONE_COMMON_FLAGS 2>/dev/null | grep 'db-backup-.*\.sql\.gz' | sort
}

BACKUP_FILE=$(list_backups | grep "db-backup-${YESTERDAY}-03" | head -1)

if [ -z "$BACKUP_FILE" ]; then
    log "No 3am backup found for ${YESTERDAY}, trying any backup from yesterday..."
    BACKUP_FILE=$(list_backups | grep "db-backup-${YESTERDAY}-" | head -1)
fi

if [ -z "$BACKUP_FILE" ]; then
    log "No yesterday backup found, falling back to oldest available..."
    BACKUP_FILE=$(list_backups | head -1)
fi

if [ -z "$BACKUP_FILE" ]; then
    log "ERROR: No backup files found in Google Drive. Cannot restore."
    # Start app anyway so health check passes (will show DB error rather than hang)
else
    log "Selected backup: $BACKUP_FILE"

    # Download
    log "Downloading $BACKUP_FILE (~100MB)..."
    if ! rclone copy "gdrive:$BACKUP_FILE" /tmp/ $RCLONE_COMMON_FLAGS 2>>"$LOG"; then
        log "ERROR: Failed to download backup"
    else
        log "Download complete. Waiting for database..."

        # Wait for MySQL to be reachable
        for i in $(seq 1 30); do
            if mysqladmin ping -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl --silent 2>/dev/null; then
                break
            fi
            log "  attempt $i/30 - database not ready..."
            sleep 5
        done

        # Drop and recreate the database for a clean restore
        log "Recreating database..."
        mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl \
            -e "DROP DATABASE IF EXISTS \`${DB_DATABASE}\`; CREATE DATABASE \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>>"$LOG"

        # Restore
        log "Restoring database (this takes a few minutes)..."
        if gunzip -c "/tmp/$BACKUP_FILE" | mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl "$DB_DATABASE" 2>>"$LOG"; then
            log "Database restore complete."

            # Extract timestamp from filename: db-backup-YYYYMMDD-HHMMSS.sql.gz
            RAW=$(echo "$BACKUP_FILE" | grep -oE '[0-9]{8}-[0-9]{6}')
            SNAPSHOT_TIME=$(echo "$RAW" | sed 's/\([0-9]\{4\}\)\([0-9]\{2\}\)\([0-9]\{2\}\)-\([0-9]\{2\}\)\([0-9]\{2\}\)\([0-9]\{2\}\)/\1-\2-\3 \4:\5:\6/')
            echo "${SNAPSHOT_TIME} UTC" > /var/www/storage/framework/yesterday-snapshot.txt
            chown www-data:www-data /var/www/storage/framework/yesterday-snapshot.txt
            log "Snapshot timestamp written: ${SNAPSHOT_TIME} UTC"
        else
            log "ERROR: Database restore failed."
        fi

        rm -f "/tmp/$BACKUP_FILE"
    fi
fi

# ── Warm up Laravel ───────────────────────────────────────────────────────────

(
    php /var/www/artisan config:cache 2>/dev/null || true
    php /var/www/artisan route:cache 2>/dev/null || true
    php /var/www/artisan view:cache 2>/dev/null || true
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
) &

# Start supervisord (nginx + php-fpm + cron — no queue worker needed)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
