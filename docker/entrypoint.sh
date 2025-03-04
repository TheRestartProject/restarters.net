#!/bin/bash
set -e

# Run the startup script if it exists
if [ -f /var/www/docker/startup.sh ]; then
    echo "Running startup script..."
    bash /var/www/docker/startup.sh
else
    echo "Startup script not found. Skipping initialization."
fi

# Check if the command is php-fpm
if [ "$1" = "php-fpm" ]; then
    # Make sure our script is executable
    chmod +x /var/www/docker/run-services.sh
    # Execute our custom script that runs both webpack and php-fpm
    exec /var/www/docker/run-services.sh
else
    # Execute the original command
    exec "$@"
fi 