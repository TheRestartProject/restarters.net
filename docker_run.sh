#
# This is what gets run when we start the restarters Docker container.
#
# We install composer dependencies in here rather than during the build step so that if we switch branches
# and restart the container, it works.

if [ ! -f .env ]
then
 cp .env.example .env
fi

rm -rf vendor
composer install

# Point at the DB server
sed -i 's/DB_HOST=.*$/DB_HOST=restarters_db/g' .env
sed -i 's/DB_DATABASE=.*$/DB_DATABASE=restarters_db_test/g' .env

# Turn off session domain, which causes problems in a Docker environment.
sed -i 's/SESSION_DOMAIN=.*$/SESSION_DOMAIN=/g' .env

# Change the Discourse host to point at the one defined in docker-compose
sed -i 's/DISCOURSE_URL=.*$/DISCOURSE_URL=http:\/\/restarters_discourse:80/g' .env

# Change the Discourse secret to be the value we set up in Discourse itself.
sed -i 's/DISCOURSE_SECRET=.*$/DISCOURSE_SECRET=mustbetencharacters/g' .env

# Change the database environment used for automated tests.
sed -i 's/SESSION_DOMAIN=.*$/SESSION_DOMAIN=/g' phpunit.xml
sed -i 's/DB_TEST_HOST=.*$/DB_TEST_HOST=restarters_db/g' phpunit.xml

mkdir storage/framework/cache/data
php artisan migrate
npm install --legacy-peer-deps
npm rebuild node-sass
php artisan lang:js --no-lib resources/js/translations.js
chmod -R 777 public

npm run watch-poll&
php artisan key:generate
php artisan cache:clear
php artisan config:clear

# Ensure we have the admin user
echo "User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2,'consent_past_data'=>'2021-01-01','consent_future_data'=>'2021-01-01','consent_gdpr'=>'2021-01-01']);" | php artisan tinker

php artisan serve --host=0.0.0.0 --port=80

# In case everything else bombs out.
sleep infinity
