<?php

use App\Filament\Inventory\Pages\InventoryDashboard;
use App\Filament\Inventory\Pages\MovementReportPage;
use App\Filament\Inventory\Pages\RecordConsumption;
use App\Filament\Inventory\Widgets\RecentMovementsWidget;
use App\Services\Inventory\InventoryMovementReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('page_inventory_dashboard', 'web');
    Permission::findOrCreate('page_inventory_movement_report', 'web');
    Permission::findOrCreate('page_inventory_record_consumption', 'web');
    $user->givePermissionTo(['page_inventory_dashboard', 'page_inventory_movement_report', 'page_inventory_record_consumption']);
    $this->actingAs($user);
});

it('renders the inventory dashboard for authorized users', function (): void {
    if (! Schema::hasTable('inventories_order_points')) {
        $this->markTestSkipped('Inventory not installed.');
    }

    Livewire::test(InventoryDashboard::class)
        ->assertSuccessful();
});

it('renders the recent movements widget without url generation errors', function (): void {
    if (! Schema::hasTable('inventories_moves')) {
        $this->markTestSkipped('Inventory moves table missing.');
    }

    Livewire::test(RecentMovementsWidget::class)
        ->assertSuccessful();
});

it('renders the movement report page', function (): void {
    if (! Schema::hasTable('inventories_moves')) {
        $this->markTestSkipped('Inventory moves table missing.');
    }

    Livewire::test(MovementReportPage::class)
        ->assertSuccessful();
});

it('stores movement report exports on the private disk', function (): void {
    if (! Schema::hasTable('inventories_moves')) {
        $this->markTestSkipped('Inventory moves table missing.');
    }

    Storage::fake('private');

    $service = app(InventoryMovementReportService::class);
    $from = Carbon::now()->subDays(7)->startOfDay();
    $to = Carbon::now()->endOfDay();

    $pdfPath = $service->storePdf($from, $to);
    $csvPath = $service->storeCsv($from, $to);

    Storage::disk('private')->assertExists($pdfPath);
    Storage::disk('private')->assertExists($csvPath);
});

it('builds a signed download url for local private disk exports', function (): void {
    Storage::fake('private');

    $service = app(InventoryMovementReportService::class);
    $from = Carbon::now()->subDays(7)->startOfDay();
    $to = Carbon::now()->endOfDay();
    $path = $service->storePdf($from, $to);

    $url = $service->downloadUrl($path);

    expect($url)->toContain('inventory/reports/download')
        ->and($url)->toContain('signature=');

    $this->get($url)->assertSuccessful();
});

it('generates rtl arabic movement report pdfs with cairo styling', function (): void {
    if (! Schema::hasTable('inventories_moves')) {
        $this->markTestSkipped('Inventory moves table missing.');
    }

    app()->setLocale('ar');

    $html = view('inventory.pdf.movement-report', [
        'moves' => collect(),
        'from'  => Carbon::now()->subDays(7)->startOfDay(),
        'to'    => Carbon::now()->endOfDay(),
    ])->render();

    expect($html)
        ->toContain('dir="rtl"')
        ->and($html)->toContain('Cairo');
});

it('renders the record consumption page when internal transfers are hidden', function (): void {
    if (! Schema::hasTable('inventory_consumption_logs')) {
        $this->markTestSkipped('Inventory consumption schema missing.');
    }

    Livewire::test(RecordConsumption::class)
        ->assertSuccessful();
});
