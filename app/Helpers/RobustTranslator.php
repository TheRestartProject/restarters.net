<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Translation\Translator as BaseTranslator;

class RobustTranslator extends BaseTranslator
{
    /**
     * @param string $key
     * @param array $replace
     * @param null $locale
     * @param bool $fallback
     *
     * @return array|null|string|void
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $translation = parent::get($key, $replace, $locale, $fallback);

        // Check whether this key is something for which we expect a translation.  Exclude things in the JSON files,
        // i.e. without a dot, as these do contain values which are validly the same as the key in English.
        if (strpos($key, '.') !== FALSE && $translation === $key) {
            // This is very likely to be an error, where we have failed to translate something or fat-fingered the key.
            \Sentry\captureMessage('Translation not found for ' . $key);
            Log::warning('Translation not found for ' . $key);
        }

        return $translation;
    }
}