#!/bin/bash
#
# Fail on error.
set -e

php artisan translations:import
php artisan translations:export --all
php ./tools/php-cs-fixer.phar fix --config=.php-cs-fixer.dist.php lang/
git diff resources/ > /tmp/translations.diff

# Can't let grep set return code.
export COUNT=`{ grep -c "/en/" /tmp/translations.diff || true; };`

if [ $COUNT -gt 0 ]
then
    # Any changes to English are probably code changes which need reflecting on live.
    export MAILGUN_KEY=`cat /etc/mailgun.key`

    curl -s --user "api:$MAILGUN_KEY" \
      https://api.eu.mailgun.net/v3/eu.mg.rstrt.org/messages \
      -F from="noreply@eu.mg.rstrt.org" \
      -F to="neil@therestartproject.org,edward@therestartproject.org" \
      -F subject="ACTION REQUIRED: English translations changed, manual changes on live probably required" \
      -F text="Check /tmp/translations.diff on restarters.dev"
    exit 1;
fi

# Other languages.
export COUNT=`{ git diff --stat | wc -l; };`

if [ $COUNT -gt 0 ]
then
    # We have some non-English changes.  This have been made on live and need to make it into the codebase.
    export BRANCH=translations_`date -I`
    git branch $BRANCH
    git checkout $BRANCH
    git add lang
    git commit -m "Update translations from live system"

    curl -s --user "api:$MAILGUN_KEY" \
      https://api.eu.mailgun.net/v3/eu.mg.rstrt.org/messages \
      -F from="noreply@eu.mg.rstrt.org" \
      -F to="neil@therestartproject.org,edward@therestartproject.org" \
      -F subject="ACTION REQUIRED: Translations to commit to codebase" \
      -F text="$BRANCH staged in /var/www/restarters.yesterday; please push and raise a PR."
    exit 1;
fi