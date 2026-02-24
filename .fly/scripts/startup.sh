#!/usr/bin/env bash

# Do NOT use set -e — we must always reach supervisord at the end
# so that nginx starts and the health check passes.

# Ensure storage directories exist
mkdir -p /var/www/storage/framework/{sessions,views,cache/data}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Substitute environment variables in nginx config for Tigris proxy.
if [ -n "$AWS_BUCKET" ]; then
    export TIGRIS_BUCKET_URL="https://${AWS_BUCKET}.fly.storage.tigris.dev"
    export TIGRIS_BUCKET_HOST="${AWS_BUCKET}.fly.storage.tigris.dev"
    envsubst '${TIGRIS_BUCKET_URL} ${TIGRIS_BUCKET_HOST}' < /etc/nginx/nginx.conf > /etc/nginx/nginx.conf.tmp
    mv /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf
fi

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
