#!/usr/bin/env bash

# Do NOT use set -e — we must always reach supervisord at the end
# so that nginx starts and the health check passes.

# Ensure storage directories exist
mkdir -p /var/www/storage/framework/{sessions,views,cache/data}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Ensure log directories exist on the persistent volume (/var/log is mounted)
mkdir -p /var/log/nginx
mkdir -p /var/log/sysstat
# Symlink sysstat SA data to persistent volume
rm -rf /var/log/sa 2>/dev/null
ln -sf /var/log/sysstat /var/log/sa

# Move file cache to persistent volume so it survives redeploys
mkdir -p /var/log/cache/data
rm -rf /var/www/storage/framework/cache/data
ln -sf /var/log/cache/data /var/www/storage/framework/cache/data
chown -R www-data:www-data /var/log/cache

# Move Laravel logs to persistent volume so they survive redeploys
mkdir -p /var/log/laravel
rm -rf /var/www/storage/logs
ln -sf /var/log/laravel /var/www/storage/logs
chown -R www-data:www-data /var/log/laravel

# Set up cookie-based site gate for non-production deployments (prevents scraping).
# Uses nginx auth_request + a tiny PHP cookie-checker. No browser dialog — the user
# sees an HTML form once, sets a cookie, and is never prompted again (including AJAX).
if [ "${BASIC_AUTH_ENABLED:-}" = "true" ]; then
    printf 'auth_request /_auth_check;\nerror_page 401 = @auth_gate;\n' \
        > /etc/nginx/auth-gate.conf
else
    : > /etc/nginx/auth-gate.conf
fi

# Substitute environment variables in nginx config for Tigris proxy.
# Always run envsubst — nginx fails to start if the variables remain as literals.
# nginx set/proxy_set_header directives require non-empty values, so fall back to
# dummy localhost values when AWS_BUCKET is not configured (dev/local-storage mode).
# In that case /uploads/ proxying simply returns 502, which is fine because dev
# stores uploads locally (served at /storage/, not /uploads/).
export TIGRIS_BUCKET_URL="${AWS_BUCKET:+https://${AWS_BUCKET}.fly.storage.tigris.dev}"
export TIGRIS_BUCKET_HOST="${AWS_BUCKET:+${AWS_BUCKET}.fly.storage.tigris.dev}"
export TIGRIS_BUCKET_URL="${TIGRIS_BUCKET_URL:-http://localhost:9999}"
export TIGRIS_BUCKET_HOST="${TIGRIS_BUCKET_HOST:-localhost}"
envsubst '${TIGRIS_BUCKET_URL} ${TIGRIS_BUCKET_HOST}' < /etc/nginx/nginx.conf > /etc/nginx/nginx.conf.tmp
mv /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf

# Run DB setup in a subshell so failures never prevent supervisord from starting
(
    # Wait for MySQL to be reachable
    echo "Waiting for database..."
    DB_READY=0
    for i in $(seq 1 30); do
        if php /var/www/artisan migrate:status > /dev/null 2>&1; then
            DB_READY=1
            break
        fi
        echo "  attempt $i/30 - database not ready, retrying..."
        sleep 2
    done

    if [ "$DB_READY" = "1" ]; then
        echo "Database ready, running migrations..."
        php /var/www/artisan migrate --force || echo "WARNING: migrate failed"
        timeout 30 php /var/www/artisan translations:import 2>/dev/null || true
    else
        echo "WARNING: Database not reachable after 60s, skipping migrations"
    fi

    # Cache config/routes/views for performance (non-fatal)
    php /var/www/artisan config:cache 2>/dev/null || true
    php /var/www/artisan route:cache 2>/dev/null || true
    php /var/www/artisan view:cache 2>/dev/null || true
    php /var/www/artisan queue:restart 2>/dev/null || true

    # Re-fix ownership: the commands above run as root and may create
    # files that php-fpm (www-data) later needs to write to.
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
) &

# Start supervisord immediately (manages nginx, php-fpm, cron)
# This ensures the health check can pass while DB setup runs in background
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
