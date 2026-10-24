<?php

use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;

it('translates the default label of fields, entries and columns', function (): void {
    app()->setLocale('ar');

    expect(TextInput::make('narration')->getLabel())->toBe('ملاحظات')
        ->and(TextEntry::make('payment_state')->getLabel())->toBe('حالة الدفع')
        ->and(TextColumn::make('price_unit')->getLabel())->toBe('سعر الوحدة');
});

it('keeps explicit labels untouched and English defaults in English', function (): void {
    app()->setLocale('ar');

    expect(TextInput::make('narration')->label('تعليق خاص')->getLabel())->toBe('تعليق خاص');

    app()->setLocale('en');

    expect(TextInput::make('narration')->getLabel())->toBe('Narration');
});
