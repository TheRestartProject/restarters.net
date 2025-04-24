#
# This is what gets run when we start the restarters Docker container.
#
# We install composer dependencies in here rather than during the build step so that if we switch branches
# and restart the container, it works.
rm -rf vendor
composer install

mkdir storage/framework/cache/data
php artisan migrate
npm install --legacy-peer-deps
npm rebuild node-sass
php artisan lang:js --no-lib resources/js/translations.js
chmod -R 777 public

php artisan key:generate
php artisan cache:clear
php artisan config:clear

# Ensure we have the admin user
echo "User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2,'consent_past_data'=>'2021-01-01','consent_future_data'=>'2021-01-01','consent_gdpr'=>'2021-01-01']);" | php artisan tinker

php artisan dev --no-logs

# In case everything else bombs out.
sleep infinity
