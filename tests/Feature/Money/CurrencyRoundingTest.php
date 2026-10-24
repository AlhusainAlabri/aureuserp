<?php

use Webkul\Support\Models\Currency;

it('stores 0.001 rounding for three-decimal currencies', function (string $code): void {
    $currency = Currency::query()->where('name', $code)->firstOrFail();

    expect($currency->decimal_places)->toBe(3)
        ->and((float) $currency->rounding)->toBe(0.001);
})->with(['OMR', 'KWD', 'BHD']);

it('never returns a zero rounding even if the stored value is bad', function (): void {
    $currency = new Currency;
    $currency->setRawAttributes(['decimal_places' => 3, 'rounding' => 0]);

    expect($currency->rounding)->toBe(0.001);
});

it('rounds omani rial amounts to three decimals with the stored rounding', function (): void {
    $omr = Currency::query()->where('name', 'OMR')->firstOrFail();

    expect(float_round(2.34567, precisionRounding: $omr->rounding))->toEqualWithDelta(2.346, 0.0000001)
        ->and(float_compare(2.3456, 2.3459, precisionRounding: $omr->rounding))->toBe(0);
});
