#!/bin/bash
set -e

echo "Starting Restarters initialization..."

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env file from .env.example"
fi

# Wait for database to be ready if WAIT_FOR_DB is set
if [ "${WAIT_FOR_DB}" = "true" ]; then
    echo "Waiting for database connection..."
    max_tries=30
    tries=0
    # TODO: Use .env variables instead of hardcoding them
    while ! mysql -h restarters_db -u restarters -ps3cr3t -e "SELECT 1" >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [ $tries -gt $max_tries ]; then
            echo "Error: Could not connect to database after $max_tries attempts."
            exit 1
        fi
        echo "Waiting for database connection... ($tries/$max_tries)"
        sleep 2
    done
    echo "Database connection established!"
fi

# Install dependencies
echo "Installing Composer dependencies..."
composer install --ignore-platform-req=ext-xmlrpc

# Generate application key if not set
echo "Generating application key..."
php artisan key:generate --no-interaction

# Run database migrations
echo "Running database migrations..."
php artisan migrate --no-interaction

# Install and build frontend assets
echo "Installing NPM dependencies..."
npm install --legacy-peer-deps
echo "Rebuilding node-sass..."
npm rebuild node-sass
echo "Generating translations..."
php artisan lang:js --no-lib resources/js/translations.js

# Clear caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear

# Ensure we have the admin user
echo "Setting up admin user..."
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
        echo "Could not find User model. Using App\\User as fallback."
        USER_CLASS="App\\User"
    fi
fi

echo "Using User class: $USER_CLASS"

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

# Start development server
echo "Restarters development environment is ready!"
echo "You can access the application at http://localhost:8001"
echo "Admin user: jane@bloggs.net / passw0rd" 