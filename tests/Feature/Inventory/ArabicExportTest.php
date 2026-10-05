<?php

use App\Services\Inventory\InventoryMovementReportService;
use App\Support\Exports\Utf8Csv;
use App\Support\Pdf\ArabicText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('page_inventory_movement_report', 'web');
    $user->givePermissionTo(['page_inventory_movement_report']);
    $this->actingAs($user);
});

it('exports arabic movement report pdfs with shaped glyphs and dejavu font', function (): void {
    if (! Schema::hasTable('inventories_moves')) {
        $this->markTestSkipped('Inventory moves table missing.');
    }

    app()->setLocale('ar');
    Storage::fake('private');

    $service = app(InventoryMovementReportService::class);
    $from = Carbon::now()->subDays(7)->startOfDay();
    $to = Carbon::now()->endOfDay();

    $path = $service->storePdf($from, $to);
    $binary = Storage::disk('private')->get($path);

    expect($binary)->toStartWith('%PDF');

    $html = view('inventory.pdf.movement-report', [
        'moves' => collect(),
        'from'  => $from,
        'to'    => $to,
    ])->render();

    expect($html)
        ->toContain('dir="rtl"')
        ->and($html)->toContain(ArabicText::pdfFontFamily());

    $shaped = ArabicText::shapeHtml($html);
    $pdf = Pdf::loadHTML($shaped)->setPaper('a4', 'landscape')->output();

    expect($pdf)->toStartWith('%PDF')
        ->and($shaped)->not->toContain(__('inventory-extensions::pdf.movement_report_title'));
});

it('exports arabic movement report csvs with utf-8 bom and logical arabic headers', function (): void {
    if (! Schema::hasTable('inventories_moves')) {
        $this->markTestSkipped('Inventory moves table missing.');
    }

    app()->setLocale('ar');
    Storage::fake('private');

    $service = app(InventoryMovementReportService::class);
    $from = Carbon::now()->subDays(7)->startOfDay();
    $to = Carbon::now()->endOfDay();

    $path = $service->storeCsv($from, $to);
    $csv = Storage::disk('private')->get($path);

    expect($csv)->toStartWith(Utf8Csv::BOM)
        ->and($csv)->toContain(__('inventory-extensions::pdf.date'))
        ->and($csv)->toContain(__('inventory-extensions::pdf.product'))
        ->and($csv)->toContain(__('inventory-extensions::pdf.quantity'));
});
