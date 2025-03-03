#!/bin/bash

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Install dependencies
composer install

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
php artisan tinker --execute="
if (\App\Models\User::where('email', 'jane@bloggs.net')->count() === 0) {
    \App\Models\User::create([
        'name' => 'Jane Bloggs',
        'email' => 'jane@bloggs.net',
        'password' => Hash::make('passw0rd'),
        'role' => 2,
        'consent_past_data' => '2021-01-01',
        'consent_future_data' => '2021-01-01',
        'consent_gdpr' => '2021-01-01'
    ]);
    echo 'Admin user created successfully.\n';
} else {
    echo 'Admin user already exists.\n';
}
"

# Start development server
echo "Restarters development environment is ready!"
echo "You can access the application at http://localhost:8001"
echo "Admin user: jane@bloggs.net / passw0rd" 