<?php

use App\Enums\Inventory\DefaultProcurement;
use App\Enums\Purchases\RequestType;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Models\Inventory\InventoryReplenishmentPreference;
use App\Services\Inventory\ReplenishmentProcurementService;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Enums\OrderPointTrigger;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\PurchaseOrder;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates an internal request from an order point', function (): void {
    if (! Schema::hasTable('inventories_order_points') || ! Schema::hasColumn('purchases_orders', 'request_type')) {
        $this->markTestSkipped('Inventory or purchase extensions not installed.');
    }

    $warehouse = Warehouse::query()->first();

    if (! $warehouse) {
        $this->markTestSkipped('No warehouse seeded.');
    }

    $product = Product::factory()->create(['is_storable' => true]);

    $orderPoint = OrderPoint::factory()->create([
        'product_id'      => $product->id,
        'warehouse_id'    => $warehouse->id,
        'location_id'     => $warehouse->lot_stock_location_id,
        'product_min_qty' => 10,
        'product_max_qty' => 50,
        'qty_multiple'    => 1,
        'trigger'         => OrderPointTrigger::MANUAL,
    ]);

    $order = app(ReplenishmentProcurementService::class)->createInternalRequest(
        $orderPoint,
        RequestType::OfficeSupplies,
    );

    expect($order)->toBeInstanceOf(PurchaseOrder::class)
        ->and($order->state)->toBe(OrderState::DRAFT)
        ->and($order->request_type)->toBe(RequestType::OfficeSupplies->value)
        ->and($order->lines)->not->toBeEmpty();
});

it('creates a draft purchase order from an order point', function (): void {
    if (! Schema::hasTable('inventories_order_points')) {
        $this->markTestSkipped('Inventory not installed.');
    }

    $warehouse = Warehouse::query()->first();

    if (! $warehouse) {
        $this->markTestSkipped('No warehouse seeded.');
    }

    $product = Product::factory()->create(['is_storable' => true]);

    if (! PurchaseOrderResourceExtensions::defaultMiscSupplierId() && ! $product->sellers()->exists()) {
        $this->markTestSkipped('No supplier available for draft PO test.');
    }

    $orderPoint = OrderPoint::factory()->create([
        'product_id'      => $product->id,
        'warehouse_id'    => $warehouse->id,
        'location_id'     => $warehouse->lot_stock_location_id,
        'product_min_qty' => 10,
        'product_max_qty' => 50,
        'qty_multiple'    => 1,
        'trigger'         => OrderPointTrigger::MANUAL,
    ]);

    InventoryReplenishmentPreference::query()->updateOrCreate(
        ['order_point_id' => $orderPoint->id],
        ['default_procurement' => DefaultProcurement::DraftPo],
    );

    $order = app(ReplenishmentProcurementService::class)->createDraftPurchaseOrder($orderPoint);

    expect($order)->toBeInstanceOf(PurchaseOrder::class)
        ->and($order->state)->toBe(OrderState::DRAFT)
        ->and($order->lines)->not->toBeEmpty();
});

it('skips automatic replenishment when open procurement exists', function (): void {
    if (! Schema::hasTable('inventories_order_points') || ! Schema::hasColumn('purchases_orders', 'request_type')) {
        $this->markTestSkipped('Inventory or purchase extensions not installed.');
    }

    $warehouse = Warehouse::query()->first();

    if (! $warehouse) {
        $this->markTestSkipped('No warehouse seeded.');
    }

    $product = Product::factory()->create(['is_storable' => true]);

    $orderPoint = OrderPoint::factory()->create([
        'product_id'      => $product->id,
        'warehouse_id'    => $warehouse->id,
        'location_id'     => $warehouse->lot_stock_location_id,
        'product_min_qty' => 10,
        'product_max_qty' => 50,
        'qty_multiple'    => 1,
        'trigger'         => OrderPointTrigger::AUTOMATIC,
    ]);

    $service = app(ReplenishmentProcurementService::class);
    $first = $service->processAutomaticReplenishment($orderPoint);
    $second = $service->processAutomaticReplenishment($orderPoint);

    expect($first)->toBeInstanceOf(PurchaseOrder::class)
        ->and($second)->toBeNull();
});
