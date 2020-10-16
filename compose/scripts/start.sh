#!/bin/bash

set -o errexit
set -o pipefail
set -o nounset
set -o xtrace

export COMPOSER_ALLOW_SUPERUSER=1

composer install --dev
php artisan key:generate
php artisan migrate -v
echo "User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2]);" | php artisan tinker
npm install

npm run watch&
php artisan serve --host=0.0.0.0 --port=8181