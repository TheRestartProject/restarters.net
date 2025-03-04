#!/bin/bash
set -e

# Start webpack dev server in the background
echo "Starting webpack dev server..."
cd /var/www && npm run watch &
WEBPACK_PID=$!

# Start PHP-FPM in the foreground
echo "Starting PHP-FPM..."
exec php-fpm

# This will only execute if php-fpm exits
kill $WEBPACK_PID 