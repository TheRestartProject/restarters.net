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
     */
    public function handle(): int
    {
        // We want to scan all English translations.
        $files = scandir(base_path() . '/lang/en');

        $count = 0;

        foreach ($files as $file) {
            if ($file == '_json.php') {
                // This is a special case used for data from the DB.  Ignore it.
            } else if ($file == 'auth.php' || $file == 'passwords.php') {
                // This is a special case used for login by Laravel itself.  Ignore it.
            } else if ($file == 'validation.php') {
                // This is probably a special case used for form validation.  It throws up many errors, and these
                // are at best not visible and at worst not too bad given that they only occur in error cases.  So
                // ignore it.
            } else if ($file == 'countries.php') {
                // This is a special case used for country names.
            } else if ($file == 'pagination.php') {
                // This is probably a special case used for paging through data.
            } else if (strpos($file, '-audits')) {
                // These are for log audits and are constructed programmatically, so we can't check for them.
            } else if (strpos($file, '.php') !== false) {
                // Actual translation file (not . or ..).
                $group = substr($file, 0, strpos($file, '.'));
                // Force the 'en' locale: we scan lang/en, but \Lang::get()
                // resolves in the CURRENT locale, which under phpunit may be a
                // test locale ('UT') with no file — returning the group name
                // (a string) and breaking checkKeys' foreach.
                $keys = \Lang::get($group, [], 'en');

                $count += $this->checkKeys($keys, $group);
            }
        }

        return $count;
    }

    private function checkKeys($keys, $prefix): int
    {
        $count = 0;

        if (! is_array($keys)) {
            return 0;
        }

        foreach ($keys as $key => $value) {
            $fullKey = "$prefix.$key";

            // If the value is an array, recurse into it
            if (is_array($value)) {
                $count += $this->checkKeys($value, $fullKey);
                continue;
            }

            // Find the translation in the languages we care about.
            foreach (['fr-BE', 'fr'] as $other) {
                // First we want to check if the translation is used in the code.  If it's not, then we
                // will want to remove it and it doesn't matter if it is not translated properly.
                if (strpos($fullKey, 'groups.tag-') === 0) {
                    // This is valid - it's used in a constructed way.
                } else if (preg_match('/^admin\.reliability-\d$/', $fullKey)) {
                    // Constructed in the SPA: t(`admin.reliability-${n}`)
                    // (client/app/pages/category.vue), so grep can't see it.
                } else if (!$this->usedInCode($fullKey)) {
                    error_log("ERROR: translation key $fullKey not used in code so far as we can tell");
                    $count++;
                } else if (!\Lang::has($fullKey, $other, false)) {
                    // This is an error. If the translated value would be different, then we need to translate
                    // it.  If it would be the same, then the code would work using fallbacks, but we translate
                    // it anyway so that this check doesn't give errors.
                    error_log("ERROR: translation key $fullKey not translated into $other, in English: $value");
                    $count++;
                } else {
                    // Occasionally we want to check whether the translated values are the same as the English
                    // ones.  This might either be legit (as above) or might be a cut & paste error.
                    $translated = \Lang::get($fullKey, [], $other);

                    // json_encode for comparison as it may be a string or an array.
                    if (json_encode($translated) == json_encode($value)) {
//                        error_log("ERROR translation key $fullKey in $other is the same as English, " . json_encode($value));
//                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    private function usedInCode($key) {
        return isset($this->usedKeys()[$key]);
    }

    private $usedKeysCache = null;

    /**
     * One grep per scanned tree instead of one per key: with ~2000 keys the
     * per-key approach spawned ~8000 grep processes and took ~20 minutes
     * inside the phpunit suite. A single grep -rhoF -f <keys-file> pass per
     * tree collects every key that appears anywhere in a few seconds.
     * resources/js died at the Nuxt-migration cutover; the SPA under
     * client/ is now the main consumer.
     */
    private function usedKeys(): array {
        if ($this->usedKeysCache !== null) {
            return $this->usedKeysCache;
        }

        $keys = [];
        foreach (scandir(base_path('lang/en')) as $file) {
            if (strpos($file, '.php') === false) {
                continue;
            }
            $group = substr($file, 0, strpos($file, '.'));
            $this->collectKeys(\Lang::get($group, [], 'en'), $group, $keys);
        }

        // One in-memory haystack per scanned tree, then strpos per key —
        // same substring semantics as the old per-key grep, without the
        // process spawns.
        $haystack = '';
        foreach ([
            'resources/views/',               // retained widget/email Blade
            'client/app/',                    // Nuxt SPA (t('file.key') usage)
            'client/e2e/',                    // SPA e2e specs asserting strings
            'app/',                           // notifications, controllers, services
                 ] as $loc) {
            if (!is_dir(base_path($loc))) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($loc), \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $haystack .= file_get_contents($fileInfo->getPathname())."\n";
                }
            }
        }

        $found = [];
        foreach ($keys as $key) {
            if (strpos($haystack, $key) !== false) {
                $found[$key] = true;
            }
        }
        $this->usedKeysCache = $found;

        return $found;
    }

    private function collectKeys($keys, $prefix, array &$out): void {
        // \Lang::get($group) returns the group name (a string) when a file
        // does not resolve to a translation array (e.g. _json.php's DB data,
        // or a group with no file). Nothing to collect in that case.
        if (! is_array($keys)) {
            return;
        }

        foreach ($keys as $key => $value) {
            $fullKey = "$prefix.$key";
            if (is_array($value)) {
                $this->collectKeys($value, $fullKey, $out);
            } else {
                $out[] = $fullKey;
            }
        }
    }
}
