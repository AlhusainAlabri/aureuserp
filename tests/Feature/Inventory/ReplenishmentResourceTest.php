<?php

use App\Enums\Purchases\RequestType;
use App\Filament\Inventory\Pages\ManageReplenishment;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Inventory\Enums\OrderPointTrigger;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('view_any_inventory_replenishment', 'web');
    $user->givePermissionTo('view_any_inventory_replenishment');
    $this->actingAs($user);
});

it('detects when stock is below minimum', function (): void {
    $warehouse = Warehouse::query()->first();

    if (! $warehouse) {
        $this->markTestSkipped('No warehouse seeded.');
    }

    $product = Product::factory()->create([
        'is_storable' => true,
    ]);

    $orderPoint = OrderPoint::factory()->create([
        'product_id'      => $product->id,
        'warehouse_id'    => $warehouse->id,
        'location_id'     => $warehouse->lot_stock_location_id,
        'product_min_qty' => 10,
        'product_max_qty' => 50,
        'qty_multiple'    => 1,
        'trigger'         => OrderPointTrigger::AUTOMATIC,
    ]);

    expect(ReplenishmentResource::isBelowMinimum($orderPoint))->toBeTrue()
        ->and(ReplenishmentResource::qtyToOrder($orderPoint))->toBeGreaterThan(0);
});

it('registers replenishment navigation', function (): void {
    expect(ReplenishmentResource::shouldRegisterNavigation())->toBeTrue();
});

it('includes the below minimum preset tab', function (): void {
    $views = app(ManageReplenishment::class)->getPresetTableViews();

    expect($views)->toHaveKey('below_minimum');
});

it('exposes procurement columns on the replenishment page', function (): void {
    if (! Schema::hasTable('inventories_order_points')) {
        $this->markTestSkipped('Inventory not installed.');
    }

    Livewire::test(ManageReplenishment::class)
        ->assertSuccessful()
        ->assertTableColumnExists('procurement_preference');
});

it('can create an internal request from a replenishment row', function (): void {
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

    Livewire::test(ManageReplenishment::class)
        ->callAction(TestAction::make('createInternalRequest')->table($orderPoint), [
            'request_type' => RequestType::OfficeSupplies->value,
        ])
        ->assertNotified();
});
