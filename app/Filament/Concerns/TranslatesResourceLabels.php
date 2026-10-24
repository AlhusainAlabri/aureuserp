<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Lang;

/**
 * Translates a resource's model labels from lang/{locale}/resource-labels.php,
 * keyed by the resource's class name, instead of Filament's English
 * class-name fallback. Resources that declare their own labels keep them.
 */
trait TranslatesResourceLabels
{
    public static function getModelLabel(): string
    {
        return static::translatedResourceLabel('singular') ?? parent::getModelLabel();
    }

    public static function getPluralModelLabel(): string
    {
        $translated = static::translatedResourceLabel('plural');

        if ($translated !== null) {
            return $translated;
        }

        $plural = parent::getPluralModelLabel();

        // Filament pluralises with English rules, so an Arabic label comes back with a stray "s".
        return preg_match('/\p{Arabic}s$/u', $plural) === 1 ? substr($plural, 0, -1) : $plural;
    }

    protected static function translatedResourceLabel(string $form): ?string
    {
        $key = 'resource-labels.'.class_basename(static::class).'.'.$form;

        return Lang::has($key) ? __($key) : null;
    }
}
