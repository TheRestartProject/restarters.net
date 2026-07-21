#!/usr/bin/env bash

# Startup script for per-PR preview apps (see fly.pr.toml).
#
# Cold-boot sequence (rootfs is always fresh — Fly resets it on every start):
#   1. Serve a static "warming up" page on port 80 immediately, so the Fly
#      proxy has something to route to and the health check passes within
#      seconds. Visitors get an auto-refreshing page instead of a proxy error.
#   2. Start a local MariaDB and restore the most recent hourly production
#      backup from Google Drive.
#   3. Truncate the restored queue tables (they contain production's
#      in-flight jobs, which must never run here).
#   4. Run the PR's migrations against the restored copy, recording the
#      outcome in /_preview_status for the deploy workflow to report.
#   5. Swap the warming page for the real app (supervisord).
#
# Suspend/resume never re-runs this script — the whole VM (MariaDB included)
# is snapshotted, so wakes after idle are sub-second.
#
# Do NOT use set -e — we must always reach supervisord at the end.

LOG=/var/log/preview-restore.log
STATUS_FILE=/var/www/storage/framework/preview-status.json

log() { echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): $*" | tee -a "$LOG"; }

# Machine-readable status served at /_preview_status (no PII).
# write_status <phase> <restore_ok> <migrate_ok> [detail]
write_status() {
    printf '{"phase":"%s","restore_ok":%s,"migrate_ok":%s,"detail":"%s","updated_at":"%s"}\n' \
        "$1" "$2" "$3" "${4:-}" "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" > "$STATUS_FILE"
    chown www-data:www-data "$STATUS_FILE" 2>/dev/null
}

# Ensure storage directories exist
mkdir -p /var/www/storage/framework/{sessions,views,cache/data}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache
mkdir -p /var/log/nginx
mkdir -p /var/log/sysstat
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

write_status "starting" false false

# Previews only restore — they never create backups, and never restart the
# yesterday app.
( crontab -l 2>/dev/null | grep -v 'db-backup' | grep -v 'restart-yesterday' ) | crontab -

# ── 1. Warming page: bind port 80 within seconds ─────────────────────────────

mkdir -p /tmp/warming
cat > /tmp/warming/index.html <<'HTML'
<!DOCTYPE html>
<html>
<head>
    <!-- Required: this page contains a literal UTF-8 em-dash, and with no
         declared charset the browser falls back to a legacy encoding and
         renders it as mojibake ("â€”"). -->
    <meta charset="utf-8">
    <title>Preview warming up</title>
    <meta http-equiv="refresh" content="15">
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 15vh; color: #333; }
        .spinner { font-size: 48px; animation: spin 2s linear infinite; display: inline-block; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="spinner">&#9881;</div>
    <h1>This preview is warming up</h1>
    <p>The database is being restored — usually a few minutes.</p>
    <p>This page refreshes automatically.</p>
</body>
</html>
HTML
echo "User-agent: *
Disallow: /" > /tmp/warming/robots.txt

cat > /tmp/warming-nginx.conf <<'CONF'
user www-data;
pid /tmp/warming-nginx.pid;
events { worker_connections 128; }
http {
    default_type text/html;
    server {
        listen 80 default_server;
        listen [::]:80 default_server;
        root /tmp/warming;

        # Matches the main nginx-fly.conf. Without it nginx sends no charset,
        # so the holding page's em-dash renders as mojibake even in browsers
        # that would otherwise sniff UTF-8.
        charset utf-8;

        location = /robots.txt { }
        location = /_preview_status {
            default_type application/json;
            alias /var/www/storage/framework/preview-status.json;
            add_header Cache-Control "no-store";
        }
        location / {
            add_header Retry-After 30 always;
            add_header Cache-Control "no-store" always;
            return 503;
        }
        error_page 503 /index.html;
        location = /index.html { internal; }
    }
}
CONF

nginx -c /tmp/warming-nginx.conf && log "Warming page up on port 80."

# ── 2. Auth gate + Tigris config for the real nginx (same as production) ─────

if [ "${BASIC_AUTH_ENABLED:-}" = "true" ]; then
    EXPECTED=$(php -r "echo hash_hmac('sha256', getenv('BASIC_AUTH_PASSWORD') ?: 'preview', getenv('APP_KEY') ?: 'fallback');")
    printf 'map $cookie_site_auth $auth_gate_valid {\n    "%s" 1;\n    default 0;\n}\nmap $auth_gate_valid $auth_check_status {\n    1 200;\n    default 401;\n}\n' \
        "$EXPECTED" > /etc/nginx/auth-gate-map.conf
    printf 'auth_request /_auth_check;\nerror_page 401 = @auth_gate;\n' \
        > /etc/nginx/auth-gate.conf
else
    printf 'map $cookie_site_auth $auth_gate_valid { default 1; }\nmap $auth_gate_valid $auth_check_status { default 200; }\n' \
        > /etc/nginx/auth-gate-map.conf
    : > /etc/nginx/auth-gate.conf
fi

if [ -n "$AWS_BUCKET" ]; then
    export TIGRIS_BUCKET_URL="https://${AWS_BUCKET}.fly.storage.tigris.dev"
    export TIGRIS_BUCKET_HOST="${AWS_BUCKET}.fly.storage.tigris.dev"
    envsubst '${TIGRIS_BUCKET_URL} ${TIGRIS_BUCKET_HOST}' < /etc/nginx/nginx.conf > /etc/nginx/nginx.conf.tmp
    mv /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf
fi

# ── 3. Start local MariaDB ────────────────────────────────────────────────────

log "Starting local MariaDB..."
write_status "restoring" false false
mkdir -p /var/lib/mysql /run/mysqld
chown -R mysql:mysql /var/lib/mysql /run/mysqld

if [ ! -d /var/lib/mysql/mysql ]; then
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

# 512M buffer pool: the VM has 2GB and no swap (a suspend requirement), so
# leave room for PHP-FPM.
mysqld_safe --user=mysql --skip-networking=0 --bind-address=127.0.0.1 \
    --innodb-buffer-pool-size=512M \
    --innodb-flush-log-at-trx-commit=2 &

for i in $(seq 1 30); do
    if mysqladmin ping --silent 2>/dev/null; then break; fi
    sleep 2
done

mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE:-restarters}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-restarters}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD:-restarters}';" 2>/dev/null
mysql -e "GRANT ALL ON \`${DB_DATABASE:-restarters}\`.* TO '${DB_USERNAME:-restarters}'@'127.0.0.1'; FLUSH PRIVILEGES;" 2>/dev/null
# The production backup contains triggers with DEFINER='restarters'@'%'. Create that user locally
# so trigger execution doesn't fail (bind-address=127.0.0.1 so '%' can't connect from outside).
mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-restarters}'@'%' IDENTIFIED BY '${DB_PASSWORD:-restarters}';" 2>/dev/null
mysql -e "GRANT ALL ON \`${DB_DATABASE:-restarters}\`.* TO '${DB_USERNAME:-restarters}'@'%'; FLUSH PRIVILEGES;" 2>/dev/null

log "MariaDB ready."

# ── 4. Restore the most recent backup ─────────────────────────────────────────

RCLONE_FLAGS="--drive-root-folder-id=$GDRIVE_BACKUP_FOLDER_ID --drive-team-drive=$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE"

RESTORE_OK=false
BACKUP_FILE=$(rclone lsf "gdrive:" $RCLONE_FLAGS 2>/dev/null | grep 'db-backup-.*\.sql\.gz' | sort | tail -1)

if [ -z "$BACKUP_FILE" ]; then
    log "ERROR: No backup files found in Google Drive. Starting with empty database."
else
    log "Restoring most recent backup: $BACKUP_FILE"

    if rclone copy "gdrive:$BACKUP_FILE" /tmp/ $RCLONE_FLAGS 2>>"$LOG"; then
        log "Download complete. Restoring database..."

        if gunzip -c "/tmp/$BACKUP_FILE" | mysql -u root \
            --socket=/run/mysqld/mysqld.sock \
            "${DB_DATABASE:-restarters}" 2>>"$LOG"; then
            RESTORE_OK=true
            log "Database restore complete."
        else
            log "ERROR: Database restore failed."
        fi

        rm -f "/tmp/$BACKUP_FILE"
    else
        log "ERROR: Download failed."
    fi
fi

# The backup captures production's in-flight queue jobs; the supervisord
# worker runs `queue:work database` regardless of QUEUE_CONNECTION, so these
# must never survive into the preview.
mysql -u root --socket=/run/mysqld/mysqld.sock "${DB_DATABASE:-restarters}" \
    -e "TRUNCATE TABLE jobs; TRUNCATE TABLE failed_jobs;" 2>>"$LOG" \
    && log "Cleared restored queue tables."

# ── 5. Run the PR's migrations against the restored copy ─────────────────────

MIGRATE_OK=false
if [ "$RESTORE_OK" = "true" ]; then
    write_status "migrating" true false
    log "Running migrations..."
    php /var/www/artisan migrate --force 2>&1 | tee -a "$LOG"
    # tee always succeeds - the pipeline hides migrate's exit code without this.
    if [ "${PIPESTATUS[0]}" = "0" ]; then
        MIGRATE_OK=true
        log "Migrations complete."
    else
        log "ERROR: migrate --force failed - see above."
    fi
    timeout 30 php /var/www/artisan translations:import 2>/dev/null || true
fi

# Warm up Laravel caches
php /var/www/artisan config:cache 2>/dev/null || true
php /var/www/artisan route:cache 2>/dev/null || true
php /var/www/artisan view:cache 2>/dev/null || true
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

if [ "$RESTORE_OK" = "true" ] && [ "$MIGRATE_OK" = "true" ]; then
    write_status "ready" true true
elif [ "$RESTORE_OK" = "true" ]; then
    write_status "failed" true false "migrate failed - check machine logs"
else
    write_status "failed" false false "restore failed - check machine logs"
fi

# ── 6. Hand over from the warming page to the real app ───────────────────────

if [ -f /tmp/warming-nginx.pid ]; then
    kill -QUIT "$(cat /tmp/warming-nginx.pid)" 2>/dev/null
    for _ in $(seq 1 10); do
        [ ! -f /tmp/warming-nginx.pid ] && break
        kill -0 "$(cat /tmp/warming-nginx.pid)" 2>/dev/null || break
        sleep 1
    done
    # Belt and braces: never let a stuck warming nginx block the real one.
    [ -f /tmp/warming-nginx.pid ] && kill -9 "$(cat /tmp/warming-nginx.pid)" 2>/dev/null
fi

log "Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
