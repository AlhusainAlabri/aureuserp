<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Enums\AssetCategory;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Models\Asset;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }
});

it('persists vehicle category and fields on assets', function (): void {
    $asset = Asset::factory()->create([
        'category'            => AssetCategory::Vehicle->value,
        'plate_number'        => 'B 98765',
        'registration_number' => 'REG-2026-001',
        'mileage'             => 45200,
        'status'              => AssetStatus::Available,
    ]);

    expect($asset->fresh()->category)->toBe(AssetCategory::Vehicle->value)
        ->and($asset->fresh()->plate_number)->toBe('B 98765')
        ->and($asset->fresh()->registration_number)->toBe('REG-2026-001')
        ->and($asset->fresh()->mileage)->toBe(45200);
});

it('returns translated labels for asset categories', function (): void {
    expect(AssetCategory::Vehicle->getLabel())
        ->toBe(__('assets-extensions::categories.vehicle'))
        ->and(AssetCategory::Equipment->getLabel())
        ->toBe(__('assets-extensions::categories.equipment'));
});
