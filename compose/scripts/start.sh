#!/bin/bash

echo "Inside start"
set -o errexit
set -o pipefail
set -o nounset
set -o xtrace

export COMPOSER_ALLOW_SUPERUSER=1

php composer.phar install
php artisan key:generate
php artisan migrate -v
echo "User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2]);" | php artisan tinker
npm install


php artisan serve --host=0.0.0.0 --port=8181&
npm run watch
sleep 9000
