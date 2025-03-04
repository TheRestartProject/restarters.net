#!/bin/bash
set -e

# Source the utility functions
source "$(dirname "$0")/bash_utils.sh"

# Trap to ensure we clean up properly
trap cleanup EXIT INT TERM

cleanup() {
    log_info "Cleaning up..."
    if [ -n "$WEBPACK_PID" ]; then
        # Check if process exists using kill -0 instead of ps
        if kill -0 $WEBPACK_PID 2>/dev/null; then
            log_info "Stopping webpack dev server..."
            kill $WEBPACK_PID || log_warn "Failed to kill webpack process"
        else
            log_warn "Webpack process is not running"
        fi
    fi
    log_info "Cleanup complete"
}

# Start webpack dev server in the background
log_info "Starting webpack dev server..."
cd /var/www && npm run watch &
WEBPACK_PID=$!

# Check if webpack started successfully using kill -0 instead of ps
sleep 2
if ! kill -0 $WEBPACK_PID 2>/dev/null; then
    log_error "Webpack dev server failed to start"
    exit 1
fi

log_info "Webpack dev server started with PID: $WEBPACK_PID"

# Start PHP-FPM in the foreground
log_info "Starting PHP-FPM..."
exec php-fpm

# This will only execute if php-fpm exits unexpectedly
log_error "PHP-FPM exited unexpectedly"
cleanup 