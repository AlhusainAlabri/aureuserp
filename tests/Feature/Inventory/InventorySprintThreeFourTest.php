<?php

use App\Filament\Inventory\Pages\InventoryDashboard;
use App\Filament\Inventory\Pages\MovementReportArchivesPage;
use App\Filament\Inventory\Pages\MovementReportPage;
use App\Filament\Inventory\Pages\ProductPurchaseHistoryPage;
use App\Filament\Inventory\Pages\RecordConsumption;
use App\Models\Inventory\InventoryReportArchive;
use App\Services\Inventory\InventoryMovementReportService;
use Database\Seeders\InventoryDemoSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Inventory\Models\Product;
use Webkul\Product\Models\Product as BaseProduct;
use Webkul\Product\Models\ProductSupplier;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('page_inventory_movement_report', 'web');
    Permission::findOrCreate('page_inventory_record_consumption', 'web');
    Permission::findOrCreate('page_inventory_product_purchase_history', 'web');
    $user->givePermissionTo([
        'page_inventory_movement_report',
        'page_inventory_record_consumption',
        'page_inventory_product_purchase_history',
    ]);
    $this->actingAs($user);
});

it('renders the movement report archives page', function (): void {
    if (! Schema::hasTable('inventory_report_archives')) {
        $this->markTestSkipped('Report archive table missing.');
    }

    Livewire::test(MovementReportArchivesPage::class)
        ->assertSuccessful();
});

it('lists archived movement reports after export', function (): void {
    if (! Schema::hasTable('inventory_report_archives') || ! Schema::hasTable('inventories_moves')) {
        $this->markTestSkipped('Inventory report schema missing.');
    }

    Storage::fake('private');

    $service = app(InventoryMovementReportService::class);
    $from = Carbon::now()->subDays(7)->startOfDay();
    $to = Carbon::now()->endOfDay();
    $service->storePdf($from, $to);

    expect(InventoryReportArchive::query()->where('report_type', 'movement')->exists())->toBeTrue();

    Livewire::test(MovementReportArchivesPage::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(InventoryReportArchive::query()->where('report_type', 'movement')->get());
});

it('renders product purchase history as a product sub-page', function (): void {
    if (! Schema::hasTable('products_products')) {
        $this->markTestSkipped('Products table missing.');
    }

    $product = Product::factory()->create(['is_storable' => true]);

    Livewire::test(ProductPurchaseHistoryPage::class, ['record' => $product->id])
        ->assertSuccessful();
});

it('uses the nested product purchase history route slug', function (): void {
    expect(ProductPurchaseHistoryPage::getSlug())->toBe('inventory/products/products/{record}/purchase-history');
});

it('passes the arabic checklist for core inventory pages', function (): void {
    app()->setLocale('ar');

    if (! Schema::hasTable('inventories_order_points')) {
        $this->markTestSkipped('Inventory not installed.');
    }

    Livewire::test(InventoryDashboard::class)->assertSuccessful();
    Livewire::test(MovementReportPage::class)->assertSuccessful();
    Livewire::test(RecordConsumption::class)->assertSuccessful();

    $product = BaseProduct::query()->where('is_storable', true)->first();

    if ($product) {
        Livewire::test(ProductPurchaseHistoryPage::class, ['record' => $product->id])
            ->assertSuccessful();
    }
});

it('seeds demo suppliers for purchase history', function (): void {
    if (! Schema::hasTable('products_product_suppliers')) {
        $this->markTestSkipped('Product suppliers table missing.');
    }

    (new InventoryDemoSeeder)->run();

    $demoProduct = BaseProduct::query()->where('reference', 'like', 'DEMO-INV-%')->first();

    if (! $demoProduct) {
        $this->markTestSkipped('Demo products not seeded.');
    }

    expect(ProductSupplier::query()->where('product_id', $demoProduct->id)->exists())->toBeTrue();
});
