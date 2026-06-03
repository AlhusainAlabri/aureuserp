<?php

namespace App\Filament\Contacts\Concerns;

trait ProvidesContactConfigurationResourceLabels
{
    abstract protected static function contactConfigurationTranslationKey(): string;

    public static function getModelLabel(): string
    {
        return __(static::contactConfigurationTranslationKey().'.model.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::contactConfigurationTranslationKey().'.model.plural');
    }
}
