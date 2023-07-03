<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for strings which are not translated, but should be';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // We want to scan all English translations.
        $files = scandir(base_path() . '/lang/en');

        foreach ($files as $file) {
            if ($file == '_json.php') {
                // This is a special case used for data from the DB.  Ignore it.
            } else if ($file == 'auth.php' || $file == 'passwords.php') {
                // This is a special case used for login by Laravel itself.  Ignore it.
            } else if ($file == 'validation.php') {
                // This is probably a special case used for form validation.  It throws up many errors, and these
                // are at best not visible and at worst not too bad given that they only occur in error cases.  So
                // ignore it.
            } else if ($file == 'pagination.php') {
                // This is probably a special case used for paging through data.
            } else if (strpos($file, '-audits')) {
                // These are for log audits and are constructed programmatically, so we can't check for them.
            } else if (strpos($file, '.php') !== false) {
                // Actual translation file (not . or ..).
                $group = substr($file, 0, strpos($file, '.'));
                $keys = \Lang::get($group);

                foreach ($keys as $key => $value) {
                    // Find the translation in the languages we care about.
                    foreach (['fr-BE', 'fr'] as $other) {
                        // First we want to check if the translation is used in the code.  If it's not, then we
                        // will want to remove it and it doesn't matter if it is not translated properly.
                        if (!$this->usedInCode("$group.$key")) {
                            error_log("ERROR: translation key $group.$key not used in code so far as we can tell");
                        } else if (!\Lang::has("$group.$key", $other, false)) {
                            // This is an error. If the translated value would be different, then we need to translate
                            // it.  If it would be the same, then the code would work using fallbacks, but we translate
                            // it anyway so that this check doesn't give errors.
                            error_log("ERROR: translation key $group.$key not translated into $other, in English: $value");
                        } else {
                            // Occasionally we want to check whether the translated values are the same as the English
                            // ones.  This might either be legit (as above) or might be a cut & paste error.
                            $translated = \Lang::get("$group.$key", [], $other);

                            // json_encode for comparison as it may be a string or an array.
                            if (json_encode($translated) == json_encode($value)) {
//                                error_log("ERROR translation key $group.$key in $other is the same as English, " . json_encode($value));
                            }
                        }
                    }
                }
            }
        }
    }

    private function usedInCode($key) {
        foreach ([
            'resources/views/',               // Blade templates
            'resources/js/components/',       // Vue templates
            'resources/js/mixins/',           // Vue mixins (rare)
            'app/',                           // Models (rare)
            'app/Notifications/',             // Email notifications
            'app/Http/Controllers/',          // Controllers (rarely)
            'app/Http/Middleware/',           // Middleware(rarely)
            'app/Services/',                  // Services(rarely)
            'app/'                            // Models rarely)
                 ] as $loc) {
            $cmd = 'grep -r "' . addslashes($key) . '" ' . $loc . ' > /dev/null';
            system($cmd, $rc);

            if ($rc == 0) {
                return true;
            }
        }

        return false;
    }
}
