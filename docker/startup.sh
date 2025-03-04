#!/bin/bash
set -e

# Source the utility functions
source "$(dirname "$0")/bash_utils.sh"

log_info "Starting Restarters initialization..."

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    log_info "Created .env file from .env.example"
fi

# Wait for database to be ready if WAIT_FOR_DB is set
if [ "${WAIT_FOR_DB}" = "true" ]; then
    log_info "Waiting for database connection..."
    max_tries=30
    tries=0
    
    # Get database connection details from .env if available
    if [ -f .env ]; then
        DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
        DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
        DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    fi
    
    # Use default values if not found in .env
    DB_HOST=${DB_HOST:-restarters_db}
    DB_USERNAME=${DB_USERNAME:-restarters}
    DB_PASSWORD=${DB_PASSWORD:-s3cr3t}
    
    while ! mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [ $tries -gt $max_tries ]; then
            log_error "Could not connect to database after $max_tries attempts."
            exit 1
        fi
        log_info "Waiting for database connection... ($tries/$max_tries)"
        sleep 2
    done
    log_info "Database connection established!"
fi

# Install dependencies
log_info "Installing Composer dependencies..."
composer install --ignore-platform-req=ext-xmlrpc

# Generate application key if not set
log_info "Checking application key..."
if ! grep -q "^APP_KEY=[A-Za-z0-9+/]\{40\}=$" .env; then
    log_info "Generating application key..."
    php artisan key:generate --no-interaction
else
    log_info "Application key already exists."
fi

# Run database migrations
log_info "Running database migrations..."
php artisan migrate --no-interaction

# Install and build frontend assets
log_info "Installing NPM dependencies..."
npm install --legacy-peer-deps
log_info "Rebuilding node-sass..."
npm rebuild node-sass
log_info "Generating translations..."
php artisan lang:js --no-lib resources/js/translations.js

# Clear caches
log_info "Clearing caches..."
php artisan cache:clear
php artisan config:clear

# Ensure we have the admin user
log_info "Setting up admin user..."
# Check if we're using the App\User or App\Models\User namespace
if grep -q "namespace App\\Models;" app/User.php 2>/dev/null; then
    USER_CLASS="App\\Models\\User"
elif grep -q "namespace App;" app/User.php 2>/dev/null; then
    USER_CLASS="App\\User"
else
    # Try to find the User model
    USER_FILE=$(find app -name "User.php" | head -n 1)
    if [ -n "$USER_FILE" ]; then
        USER_NAMESPACE=$(grep "namespace" "$USER_FILE" | sed 's/namespace \(.*\);/\1/')
        USER_CLASS="${USER_NAMESPACE}\\User"
    else
        log_warn "Could not find User model. Using App\\User as fallback."
        USER_CLASS="App\\User"
    fi
fi

log_info "Using User class: $USER_CLASS"

php artisan tinker --execute="
if (class_exists('$USER_CLASS')) {
    \$userClass = '$USER_CLASS';
    if (\$userClass::where('email', 'jane@bloggs.net')->count() === 0) {
        \$userClass::create([
            'name' => 'Jane Bloggs',
            'email' => 'jane@bloggs.net',
            'password' => \Hash::make('passw0rd'),
            'role' => 2,
            'consent_past_data' => '2021-01-01',
            'consent_future_data' => '2021-01-01',
            'consent_gdpr' => '2021-01-01'
        ]);
        echo 'Admin user created successfully.\n';
    } else {
        echo 'Admin user already exists.\n';
    }
} else {
    echo 'User class not found. Please check the namespace.\n';
}
"

# Create a file to indicate successful initialization
touch storage/framework/initialized

log_info "Restarters development environment is ready!"
log_info "You can access the application at http://localhost:8001"
log_info "Admin user: jane@bloggs.net / passw0rd" 