<?php

use App\Filament\Extensions\AccountingResourceExtensions;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Support\DefaultCurrency;
use Webkul\Security\Settings\CurrencySettings;
use Webkul\Support\Models\Currency;

function useDefaultCurrency(?string $code): void
{
    $settings = app(CurrencySettings::class);
    $settings->default_currency_id = $code === null ? null : Currency::query()->where('name', $code)->value('id');
    $settings->save();

    app()->forgetInstance(DefaultCurrency::class);
}

it('follows the default currency chosen in settings', function (): void {
    useDefaultCurrency('OMR');

    expect(DefaultCurrency::code())->toBe('OMR');
});

it('falls back to APP_CURRENCY when no default currency is chosen', function (): void {
    config(['app.currency' => 'SAR']);
    useDefaultCurrency(null);

    expect(DefaultCurrency::code())->toBe('SAR');
});

it('formats money helper amounts in the default currency with its own decimals', function (): void {
    useDefaultCurrency('OMR');
    app()->setLocale('en');

    expect(money(2.35))->toContain('2.350')
        ->and(money(2.35))->not->toContain('$');
});

it('gives new invoices, bills and purchase orders the default currency', function (): void {
    useDefaultCurrency('OMR');

    $omr = Currency::query()->where('name', 'OMR')->value('id');

    expect(DefaultCurrency::id())->toBe($omr)
        ->and(AccountingResourceExtensions::defaultOmrCurrencyId())->toBe($omr)
        ->and(PurchaseOrderResourceExtensions::defaultOmrCurrencyId())->toBe($omr);
});

it('rounds money to the decimals of the default currency instead of a fixed two', function (): void {
    useDefaultCurrency('OMR');

    expect(DefaultCurrency::decimals())->toBe(3)
        ->and(money_round(0.5755))->toBe(0.576)
        ->and(money_round(0.5755, 2))->toBe(0.58);

    useDefaultCurrency('USD');

    expect(DefaultCurrency::decimals())->toBe(2)
        ->and(money_round(0.5755))->toBe(0.58);
});
