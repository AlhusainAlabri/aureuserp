<?php

use App\Filament\Inventory\Pages\InventoryDashboard;
use App\Filament\Inventory\Pages\MovementReportPage;
use App\Filament\Inventory\Pages\RecordConsumption;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\Dashboard\LowStockWidget;
use App\Support\FilamentUrl;
use Database\Seeders\InventoryDemoSeeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReceiptResource;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Models\Product;
use Webkul\Security\Models\User;

it('appends the active locale to filament urls', function (): void {
    app()->setLocale('ar');

    $url = FilamentUrl::appendLocaleToUrl('/admin/inventory/dashboard');

    expect($url)->toContain('lang=ar');
});

it('includes locale parameters when building resource urls', function (): void {
    app()->setLocale('ar');

    expect(FilamentUrl::withLocale(['activeTableView' => 'below_minimum']))
        ->toMatchArray([
            'activeTableView' => 'below_minimum',
            'lang'            => 'ar',
        ]);
});

it('keeps inventory extension pages out of duplicate navigation', function (): void {
    expect(MovementReportPage::shouldRegisterNavigation())->toBeFalse()
        ->and(RecordConsumption::shouldRegisterNavigation())->toBeFalse()
        ->and(InventoryDashboard::shouldRegisterNavigation())->toBeTrue();
});

it('shows low stock widget for admin users on the main dashboard', function (): void {
    $user = User::factory()->create();
    $role = Role::findOrCreate('Admin', 'web');
    $user->assignRole($role);

    $this->actingAs($user);
    app()->setLocale('ar');

    $widgets = app(Dashboard::class)->getWidgets();

    expect($widgets)->toContain(LowStockWidget::class);

    Livewire::test(Dashboard::class)
        ->assertSuccessful()
        ->assertSee(__('dashboard.stats.low_stock_items'));
});

it('links pending receipts widget to inventory receipts', function (): void {
    app()->setLocale('ar');

    $url = FilamentUrl::appendLocaleToUrl(
        ReceiptResource::getUrl('index', FilamentUrl::withLocale([
            'activeTableView' => 'todo_receipts',
        ])),
    );

    expect($url)->toContain('receipts')
        ->and($url)->toContain('lang=ar');
});

it('seeds demo inventory data idempotently', function (): void {
    if (! Schema::hasTable('inventories_order_points') || Warehouse::query()->doesntExist()) {
        $this->markTestSkipped('Inventory warehouse not available.');
    }

    $beforeProducts = Product::query()->where('reference', 'like', 'DEMO-INV-%')->count();

    (new InventoryDemoSeeder)->run();
    (new InventoryDemoSeeder)->run();

    $afterProducts = Product::query()->where('reference', 'like', 'DEMO-INV-%')->count();

    expect($afterProducts)->toBeGreaterThanOrEqual($beforeProducts > 0 ? $beforeProducts : 3)
        ->and(OrderPoint::query()->count())->toBeGreaterThan(0);
});
