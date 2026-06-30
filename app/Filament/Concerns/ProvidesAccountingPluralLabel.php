<?php

namespace App\Filament\Concerns;

trait ProvidesAccountingPluralLabel
{
    abstract protected static function accountingPluralTranslationKey(): string;

    public static function getPluralModelLabel(): string
    {
        return __(static::accountingPluralTranslationKey().'.navigation.title');
    }
}
