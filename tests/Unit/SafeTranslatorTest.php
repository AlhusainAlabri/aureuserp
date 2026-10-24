<?php

use App\Support\Translation\SafeTranslator;

it('is the application translator', function (): void {
    expect(app('translator'))->toBeInstanceOf(SafeTranslator::class);
});

it('always returns a string for a plain word, even when a language file has that name', function (string $locale): void {
    app()->setLocale($locale);

    foreach (['Operations', 'Tasks', 'Notifications', 'Dashboard', 'Admin', 'Welcome', 'Attributes', 'Validation'] as $word) {
        expect(__($word))->toBeString();
    }
})->with(['en', 'ar']);

it('still resolves dotted keys from language files', function (): void {
    app()->setLocale('ar');

    expect(__('validation.required', ['attribute' => 'x']))->toBeString()->not->toBe('validation.required');
});
