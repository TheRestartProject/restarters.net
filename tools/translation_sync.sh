#!/bin/bash
#
# Fail on error.
set -e

php artisan translations:import
php artisan translations:export --all
php ./tools/php-cs-fixer.phar fix --config=.php-cs-fixer.dist.php resources/lang/
git diff resources/ > /tmp/translations.diff

# Can't let grep set return code.
export COUNT=`{ grep -c "/en/" /tmp/translations.diff || true; };`

if [ $COUNT -gt 0 ]
then
    curl -s --user "api:35c74863b903dc029c53791e7e554fdf-dbc22c93-75a49878" \
      https://api.eu.mailgun.net/v3/eu.mg.rstrt.org/messages \
      -F from="noreply@eu.mg.rstrt.org" \
      -F to="neil@therestartproject.org,edward@therestartproject.org" \
      -F subject="ACTION REQUIRED: English translations changed, manual changes on live probably required" \
      -F text="Check /tmp/translations.diff on restarters.def"
    exit 1;
fi

# TODO Check for deleted keys.