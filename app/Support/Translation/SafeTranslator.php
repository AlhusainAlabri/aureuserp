<?php

namespace App\Support\Translation;

use Illuminate\Translation\Translator;

/**
 * Translating a plain word ("Operations", "Tasks") looks for a language file with that name when the word is
 * missing from the JSON file, and on a case-insensitive disk (macOS) it finds lang/xx/operations.php and returns
 * the whole file as an array. Labels then crash. A key without a dot is always a plain string.
 */
class SafeTranslator extends Translator
{
    /**
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true): string|array
    {
        $line = parent::get($key, $replace, $locale, $fallback);

        if (is_array($line) && is_string($key) && ! str_contains($key, '.')) {
            return $key;
        }

        return $line;
    }
}
