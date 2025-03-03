#!/bin/bash
set -e

# Run the startup script if it exists
if [ -f /var/www/docker/startup.sh ]; then
    echo "Running startup script..."
    bash /var/www/docker/startup.sh
else
    echo "Startup script not found. Skipping initialization."
fi

# Execute the main command (usually php-fpm)
exec "$@" 