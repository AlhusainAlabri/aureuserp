<?php

use App\Console\Commands\NotifyLowStock;
use App\Mail\InventoryLowStockMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Webkul\Inventory\Enums\OrderPointTrigger;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Security\Models\User;

it('queues low stock mail and database notifications', function (): void {
    if (! Schema::hasTable('inventories_order_points')) {
        $this->markTestSkipped('Inventory not installed.');
    }

    Mail::fake();

    $warehouse = Warehouse::query()->first();

    if (! $warehouse) {
        $this->markTestSkipped('No warehouse seeded.');
    }

    $product = Product::factory()->create([
        'is_storable' => true,
    ]);

    OrderPoint::factory()->create([
        'product_id'      => $product->id,
        'warehouse_id'    => $warehouse->id,
        'location_id'     => $warehouse->lot_stock_location_id,
        'product_min_qty' => 10,
        'product_max_qty' => 50,
        'qty_multiple'    => 1,
        'trigger'         => OrderPointTrigger::AUTOMATIC,
    ]);

    $manager = User::factory()->create();
    Permission::findOrCreate('page_inventory_dashboard', 'web');
    $manager->givePermissionTo('page_inventory_dashboard');

    $this->artisan(NotifyLowStock::class)->assertSuccessful();

    Mail::assertQueued(InventoryLowStockMail::class);
});
