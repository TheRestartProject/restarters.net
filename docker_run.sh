#
# This is what gets run when we start the restarters Docker container.
#
# We install composer dependencies in here rather than during the build step so that if we switch branches
# and restart the container, it works.

USER_ID=${UID:-1000}
GROUP_ID=${GID:-1000}

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

echo "Fixing file permissions with ${USER_ID}:${GROUP_ID}"
# Only change ownership of directories that need it, excluding .git and other system files
# This prevents permission errors on files owned by the host system
for dir in storage bootstrap/cache vendor node_modules public/uploads; do
    if [ -d "$dir" ]; then
        chown -R ${USER_ID}:${GROUP_ID} "$dir" 2>/dev/null || true
    fi
done

# Ensure storage directories exist and have correct permissions
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

php artisan migrate
npm install --legacy-peer-deps
npm rebuild node-sass
php artisan lang:js --no-lib resources/js/translations.js

npm run watch-poll&
php artisan key:generate
php artisan cache:clear
php artisan config:clear

# Ensure we have the admin user
echo "User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2,'consent_past_data'=>'2021-01-01','consent_future_data'=>'2021-01-01','consent_gdpr'=>'2021-01-01']);" | php artisan tinker

php-fpm

# In case everything else bombs out.
sleep infinity
