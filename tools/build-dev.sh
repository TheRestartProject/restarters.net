#!/bin/bash

while getopts "adibcm" opt; do
  case $opt in
    a)
      ALL=1
      ;;
    d)
      DELETE_DEPS=1
      ;;
    i)
      INSTALL_DEPS=1
      ;;
    b)
      BUILD_ASSETS=1
      ;;
    c)
      CLEAR_CACHES=1
      ;;
    m)
      MIGRATE_DB=1
      ;;
  esac
done

if [ $OPTIND -eq 1 ]; then 
	echo "No options were passed."
	exit 1
fi

banner () {
	echo
	echo "#########################################"
	echo "# $1..."
	echo "#########################################"
	echo
}

if [[ $DELETE_DEPS ]]; then
banner "Removing vendor and node_modules folders"
rm -rf vendor
rm -rf node_modules
echo -e "\nDone."
fi

if [[ $ALL || $INSTALL_DEPS ]]; then
banner "Installing dependencies"
php ./composer.phar install --no-dev 
chown -R www-data vendor # needed?
npm install 
npm rebuild node-sass
chown -R www-data node_modules # needed?
echo -e "\nDone."
fi

if [[ $ALL || $BUILD_TRANSLATIONS ]]; then
banner "Building translations"
php artisan lang:js --no-lib resources/js/translations.js
echo -e "\nDone."
fi

if [[ $ALL || $BUILD_ASSETS ]]; then
banner "Building assets"
NODE_OPTIONS=--max-old-space-size=8192
npm run build
echo -e "\nDone."
fi

if [[ $ALL || $CLEAR_CACHES ]]; then
banner "Clearing caches"
php artisan config:clear 
php artisan cache:clear 
php artisan route:clear 
php artisan view:clear
echo -e "\nDone."
fi

if [[ $ALL || $MIGRATE_DB ]]; then
banner "Migrating DB"
php artisan migrate
echo -e "\nDone."
fi

echo -e "\n\n\nFinished."
