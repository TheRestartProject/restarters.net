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

# Generic wait function that takes: service_name, check_command, max_attempts, sleep_interval
wait_for_service() {
  local service_name="$1"
  local check_command="$2" 
  local max_attempts="$3"
  local sleep_interval="$4"
  
  echo "Waiting for $service_name..."
  local attempt=0
  while [ $attempt -lt $max_attempts ]; do
    if eval "$check_command" >/dev/null 2>&1; then
      echo "✓ $service_name is ready"
      return 0
    fi
    echo "  $service_name not ready, waiting... (attempt $((attempt + 1))/$max_attempts)"
    sleep "$sleep_interval"
    attempt=$((attempt + 1))
  done
  echo "❌ $service_name failed to start after $max_attempts attempts"
  exit 1
}

# Ensure storage directories exist and have correct permissions
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p uploads
mkdir -p public/uploads

# Only change ownership of directories that need it, excluding .git and other system files
# This prevents permission errors on files owned by the host system
echo "Fixing file permissions with ${USER_ID}:${GROUP_ID}"
for dir in storage bootstrap/cache vendor node_modules uploads public/uploads; do
    if [ -d "$dir" ]; then
        chown -R ${USER_ID}:${GROUP_ID} "$dir" 2>/dev/null || true
    fi
done

# Wait for MySQL database to be ready before running migrations
wait_for_service "MySQL database" "nc -z restarters_db 3306" 60 5

php artisan migrate
npm install --legacy-peer-deps
npm rebuild node-sass
php artisan lang:js --no-lib resources/js/translations.js

# Install Playwright for testing (system deps already in Dockerfile)
npm install -D @playwright/test
npx playwright install

npm run watch-poll&
php artisan key:generate
php artisan cache:clear
php artisan config:clear

# Ensure we have the admin user
echo "User::firstOrCreate(['email'=>'jane@bloggs.net'], ['name'=>'Jane Bloggs','password'=>Hash::make('passw0rd'),'role'=>2,'consent_past_data'=>'2021-01-01','consent_future_data'=>'2021-01-01','consent_gdpr'=>'2021-01-01']);" | php artisan tinker

php-fpm

# In case everything else bombs out.
sleep infinity
