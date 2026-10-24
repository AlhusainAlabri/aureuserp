<?php

/**
 * @return list<string>
 */
function flattenTranslationKeys(array $messages, string $prefix = ''): array
{
    $keys = [];

    foreach ($messages as $key => $value) {
        $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $keys = array_merge($keys, flattenTranslationKeys($value, $path));

            continue;
        }

        $keys[] = $path;
    }

    return $keys;
}

it('translates every framework message into Arabic', function (string $file): void {
    $english = require base_path("vendor/laravel/framework/src/Illuminate/Translation/lang/en/{$file}.php");
    $arabic = require lang_path("ar/{$file}.php");

    $missing = array_diff(flattenTranslationKeys($english), flattenTranslationKeys($arabic));

    expect($missing)->toBe([], "lang/ar/{$file}.php is missing: ".implode(', ', $missing));
})->with(['validation', 'auth', 'passwords', 'pagination']);

it('returns Arabic validation messages for the exists and unique rules', function (): void {
    app()->setLocale('ar');

    expect(__('validation.exists', ['attribute' => 'العملة']))->toBe('القيمة المحددة في حقل العملة غير صالحة.')
        ->and(__('validation.unique', ['attribute' => 'البريد']))->toContain('مستخدمة')
        ->and(__('auth.failed'))->not->toContain('credentials');
});
