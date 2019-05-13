#!/usr/bin/env bash

set -o errexit
set -o pipefail
set -o nounset
set -o xtrace

export COMPOSER_ALLOW_SUPERUSER=1
composer install
php artisan migrate -v
php artisan serve --host=0.0.0.0 --port=8181
