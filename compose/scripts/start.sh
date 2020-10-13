#!/bin/bash

set -o errexit
set -o pipefail
set -o nounset
set -o xtrace

export COMPOSER_ALLOW_SUPERUSER=1

# We install here rather than in the Dockerfile so that we can pick up any changes made during development.
composer install --dev
php artisan key:generate
php artisan migrate -v
echo "User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2]);" | php artisan tinker

php artisan serve --host=0.0.0.0 --port=8181