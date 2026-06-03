<?php

use App\Models\Inventory\InventoryConsumptionLog;
use App\Services\Inventory\ConsumptionTransferService;
use App\Services\Inventory\ProductPurchaseHistoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Department;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Product;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('records a consumption log entry', function (): void {
    if (! Schema::hasTable('inventory_consumption_logs') || ! Schema::hasTable('inventories_operations')) {
        $this->markTestSkipped('Inventory consumption schema missing.');
    }

    if (! OperationType::query()->where('type', Webkul\Inventory\Enums\OperationType::INTERNAL)->exists()) {
        $this->markTestSkipped('No internal transfer operation type configured.');
    }

    $product = Product::factory()->create(['is_storable' => true]);
    $department = Department::query()->first();

    if (! $department) {
        $this->markTestSkipped('No department seeded.');
    }

    $result = app(ConsumptionTransferService::class)->recordConsumption(
        product: $product,
        quantity: 1,
        purpose: 'Test consumption',
        departmentId: $department->id,
    );

    expect($result['log'])->toBeInstanceOf(InventoryConsumptionLog::class)
        ->and($result['log']->product_id)->toBe($product->id)
        ->and($result['log']->purpose)->toBe('Test consumption');

    expect(InventoryConsumptionLog::query()->where('product_id', $product->id)->exists())->toBeTrue();
});

it('returns purchase history rows for a product', function (): void {
    if (! Schema::hasTable('products_product_suppliers')) {
        $this->markTestSkipped('Product suppliers table missing.');
    }

    $product = Product::factory()->create(['is_storable' => true]);

    $history = app(ProductPurchaseHistoryService::class)->historyForProduct($product);

    expect($history)->toBeInstanceOf(Collection::class);
});

it('formats omr amounts with three decimals', function (): void {
    app()->setLocale('en');
    expect(ProductPurchaseHistoryService::formatOmr(12.5))->toBe('OMR 12.500');

    app()->setLocale('ar');
    expect(ProductPurchaseHistoryService::formatOmr(12.5))->toBe('ر.ع. 12.500');
});
