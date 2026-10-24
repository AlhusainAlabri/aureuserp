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
        return static::translatedResourceLabel('plural') ?? parent::getPluralModelLabel();
    }

    protected static function translatedResourceLabel(string $form): ?string
    {
        $key = 'resource-labels.'.class_basename(static::class).'.'.$form;

        return Lang::has($key) ? __($key) : null;
    }
}
