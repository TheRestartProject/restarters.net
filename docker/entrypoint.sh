#!/bin/bash
set -e

# Source the utility functions
source "$(dirname "$0")/bash_utils.sh"

# Fix ownership to match host user
fix_permissions() {
    GROUP_ID=${HOST_GROUP_ID:-1000}

     # Ensure hostgroup exists with correct GID
    if ! getent group hostgroup >/dev/null; then
        groupadd -g ${GROUP_ID} hostgroup 2>/dev/null || \
        groupmod -n hostgroup $(getent group ${GROUP_ID} | cut -d: -f1)
    fi
    
    usermod -a -G hostgroup www-data

    # Fix the ownership of critical directories
    log_info "Ensuring proper permissions for Laravel directories"
    mkdir -p /var/www/storage/framework /var/www/storage/logs /var/www/bootstrap/cache /var/www/public
    chown -R www-data:hostgroup /var/www/storage /var/www/bootstrap/cache
    # Ensure the public subdirectories are writable by the www-data users and hostgroup
    find /var/www/public -type d -exec chmod 777 {} +
}

# Fix permissions on startup
fix_permissions


# Check if we need to run the initialization
if [ -f /var/www/docker/startup.sh ]; then
    # Check if we've already initialized
    if [ -f /var/www/storage/framework/initialized ] && [ "${FORCE_INIT}" != "true" ]; then
        log_info "Application already initialized. Skipping initialization."
    else
        log_info "Running startup script..."
        bash /var/www/docker/startup.sh || {
            log_error "Startup script failed. Check the logs for details."
            exit 1
        }
    fi
else
    log_warn "Startup script not found. Skipping initialization."
fi

# Check if the command is php-fpm
if [ "$1" = "php-fpm" ]; then
    # Make sure our script is executable
    if [ -f /var/www/docker/run-services.sh ]; then
        chmod +x /var/www/docker/run-services.sh
        # Execute our custom script that runs both webpack and php-fpm
        log_info "Starting services with run-services.sh..."
        exec /var/www/docker/run-services.sh
    else
        log_warn "run-services.sh not found. Starting PHP-FPM directly..."
        exec php-fpm
    fi
else
    # Execute the original command
    log_info "Executing command: $@"
    exec "$@"
fi 