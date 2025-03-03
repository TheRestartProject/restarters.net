#!/bin/bash

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Update database connection in .env
sed -i 's/DB_HOST=.*$/DB_HOST=restarters_db/g' .env
sed -i 's/DB_DATABASE=.*$/DB_DATABASE=restarters_db_test/g' .env
sed -i 's/DB_USERNAME=.*$/DB_USERNAME=restarters/g' .env
sed -i 's/DB_PASSWORD=.*$/DB_PASSWORD=s3cr3t/g' .env

# Install dependencies
composer install --ignore-platform-req=ext-xmlrpc

# Generate application key if not set
php artisan key:generate --no-interaction

# Run database migrations
php artisan migrate --no-interaction

# Install and build frontend assets
npm install --legacy-peer-deps
npm rebuild node-sass
php artisan lang:js --no-lib resources/js/translations.js

# Clear caches
php artisan cache:clear
php artisan config:clear

# Ensure we have the admin user
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

# Start frontend development server
npm run watch&

# Start PHP development server
php artisan serve --host=0.0.0.0 --port=80

# Start development server
echo "Restarters development environment is ready!"
echo "You can access the application at http://localhost:8001"
echo "Admin user: jane@bloggs.net / passw0rd" 