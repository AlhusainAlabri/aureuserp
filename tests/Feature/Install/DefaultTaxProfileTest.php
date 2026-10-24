<?php

use App\Services\Install\DefaultTaxProfile;
use Webkul\Account\Models\Tax;
use Webkul\Account\Models\TaxGroup;

it('sets the seeded default tax to the country VAT rate', function (): void {
    $group = TaxGroup::factory()->create(['name' => 'Tax 15%']);
    $tax = Tax::factory()->create(['name' => '15 %', 'amount' => 15, 'tax_group_id' => $group->id, 'description' => '<p>CESS 5%</p>']);

    $changed = (new DefaultTaxProfile)->apply('ar', 'OM');

    expect($changed)->toBeGreaterThanOrEqual(2)
        ->and($tax->fresh()->name)->toBe('ضريبة القيمة المضافة 5%')
        ->and((float) $tax->fresh()->amount)->toBe(5.0)
        ->and($tax->fresh()->description)->toBeNull()
        ->and($group->fresh()->name)->toBe('ضريبة القيمة المضافة 5%');
});

it('leaves taxes alone for countries without a configured rate or after a customer edit', function (): void {
    $edited = Tax::factory()->create(['name' => 'ضريبة خاصة', 'amount' => 7]);
    $seeded = Tax::factory()->create(['name' => '15 %', 'amount' => 15]);

    expect((new DefaultTaxProfile)->apply('ar', 'ZZ'))->toBe(0)
        ->and((float) $seeded->fresh()->amount)->toBe(15.0);

    (new DefaultTaxProfile)->apply('en', 'AE');

    expect($edited->fresh()->name)->toBe('ضريبة خاصة')
        ->and((float) $edited->fresh()->amount)->toBe(7.0)
        ->and($seeded->fresh()->name)->toBe('VAT 5%');
});
