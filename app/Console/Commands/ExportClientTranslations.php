<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Export lang/{en,fr,fr-BE} to vue-i18n JSON for the Nuxt client
 * (client/i18n/locales/<locale>.json). Laravel lang files remain the single
 * source of truth; the client JSONs are generated and committed, with a CI
 * check (--check) that they are in sync.
 *
 * Conversions (Laravel → vue-i18n message syntax):
 *   - :param            → {param}
 *   - {1} x|[0,*] y     → x | y            (positional plural forms; Laravel's
 *     exact/range tags are stripped — for this corpus the positional selection
 *     matches Laravel's MessageSelector, which the client parity tests pin)
 *   - literal @         → {'@'}            (vue-i18n linked-message escape)
 * A parity test suite on the client side compiles every exported message and
 * compares plural behaviour against Laravel semantics; anything this command
 * cannot convert safely aborts the export (fail loudly, never silently drop).
 */
class ExportClientTranslations extends Command
{
    protected $signature = 'translations:export-client
        {--check : Verify the committed client JSONs match a fresh export; exit 1 on drift}';

    protected $description = 'Export lang/ PHP translations to vue-i18n JSON for the Nuxt client';

    private const LOCALES = ['en', 'fr', 'fr-BE'];

    public function handle(): int
    {
        $outDir = base_path('client/i18n/locales');

        foreach (self::LOCALES as $locale) {
            $messages = $this->buildLocale($locale);
            $json = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
            $target = $outDir.'/'.$locale.'.json';

            if ($this->option('check')) {
                if (! File::exists($target) || File::get($target) !== $json) {
                    $this->error("$target is out of sync with lang/ — run: php artisan translations:export-client");

                    return self::FAILURE;
                }
            } else {
                File::ensureDirectoryExists($outDir);
                File::put($target, $json);
                $this->info("Wrote $target");
            }
        }

        if ($this->option('check')) {
            $this->info('Client translations are in sync.');
        }

        return self::SUCCESS;
    }

    private function buildLocale(string $locale): array
    {
        $messages = [];

        // lang/<locale>/*.php → nested under the file basename (file.key).
        foreach (File::glob(base_path("lang/$locale/*.php")) as $path) {
            $group = basename($path, '.php');
            $strings = include $path;

            if (! is_array($strings)) {
                $this->error("$path did not return an array");
                throw new \RuntimeException("Unexportable lang file: $path");
            }

            $messages[$group] = $this->convertArray($strings, "$locale/$group");
        }

        // lang/<locale>.json → top-level keys (device category/cluster names).
        $jsonPath = base_path("lang/$locale.json");
        if (File::exists($jsonPath)) {
            $extra = json_decode(File::get($jsonPath), true);
            if (is_array($extra)) {
                $messages = array_replace($this->convertArray($extra, "$locale.json"), $messages);
            }
        }

        ksort($messages);

        return $messages;
    }

    private function convertArray(array $strings, string $context): array
    {
        $out = [];

        foreach ($strings as $key => $value) {
            if (is_array($value)) {
                $out[$key] = $this->convertArray($value, "$context.$key");
            } elseif (is_string($value)) {
                $out[$key] = $this->convertString($value, "$context.$key");
            } else {
                // Numeric/bool values appear in a few lang files; pass through.
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function convertString(string $value, string $context): string
    {
        // Plural forms: strip Laravel's exact/range tags ({1} x, [0,*] y),
        // keeping segment order — vue-i18n selects positionally on the same
        // 0/1/2+ boundaries for these corpora (pinned by client parity tests).
        if (str_contains($value, '|')) {
            $segments = explode('|', $value);
            $stripped = array_map(function ($segment) {
                return preg_replace('/^\s*(\{\d+\}|\[\d+,(?:\d+|\*)\])\s*/', '', $segment);
            }, $segments);

            // A tagged segment list we cannot represent positionally
            // (e.g. sparse exacts like {5}) must fail the export.
            foreach ($segments as $i => $segment) {
                if (preg_match('/^\s*\{(\d+)\}/', $segment, $m)) {
                    if ((int) $m[1] !== $i && ! in_array((int) $m[1], [0, 1], true)) {
                        throw new \RuntimeException(
                            "Unconvertible plural form in $context: '$value' — segment {$m[0]} is not positional"
                        );
                    }
                }
            }

            // No spaces around the pipe: matches Laravel's own bare '|' format
            // exactly, so the exported string is byte-for-byte what a plain
            // `str_replace(':count', ..., $laravelString)` would have produced
            // pre-conversion - one less variable when comparing exported vs.
            // source copy (dashboard.md parity gap #1).
            $value = implode('|', array_map('trim', $stripped));
        }

        // Laravel :param → vue-i18n {param}. The negative lookbehind keeps
        // scheme-style colons intact (mailto:janet must not become {janet}) —
        // genuine params are always preceded by a non-word character.
        $value = preg_replace('/(?<!\w):(\w+)/', '{$1}', $value);

        // Escape literal @ (vue-i18n linked-message marker) and any literal
        // braces that existed before param conversion would be rare; @ is the
        // common hazard (email addresses in strings).
        $value = str_replace('@', "{'@'}", $value);

        return $value;
    }
}
