<?php

namespace App\Filament\Assets\Support;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class SignatureField
{
    public static function make(string $name = 'signature'): Field
    {
        if (class_exists(SignaturePad::class)) {
            return SignaturePad::make($name)
                ->label(__('assets-extensions::signatures.label'))
                ->required()
                ->columnSpanFull();
        }

        return Textarea::make($name)
            ->label(__('assets-extensions::signatures.label'))
            ->helperText(__('assets-extensions::signatures.fallback_hint'))
            ->required()
            ->columnSpanFull();
    }
}
