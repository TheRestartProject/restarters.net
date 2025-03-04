#!/bin/bash
set -e

# Source the utility functions
source "$(dirname "$0")/bash_utils.sh"

# Ensure proper permissions for Laravel storage directories
log_info "Setting proper permissions for storage directories..."
mkdir -p /var/www/storage/logs /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views
chown -R www-data:www-data /var/www/storage
chmod -R 775 /var/www/storage

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