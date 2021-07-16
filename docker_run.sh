#
# This is what gets run when we start the restarters Docker container.
#
# We install composer dependencies in here rather than during the build step so that if we switch branches
# and restart the container, it works.
rm -rf vendor
php composer.phar install --no-dev
php artisan migrate
npm install
npm rebuild node-sass

npm run watch&
php artisan key:generate
php artisan cache:clear
php artisan config:clear

# Ensure we have the admin user
echo "User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2]);" | php artisan tinker

php artisan serve --host=0.0.0.0 --port=80

# In case everything else bombs out.
bash
