<?php

use App\Filament\Invoices\Pages\ManageBillDocuments;
use App\Models\Invoices\InvoiceAttachment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Webkul\Account\Models\Move;
use Webkul\Invoice\Filament\Clusters\Vendors;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource;
use Webkul\Invoice\Models\Bill;

it('uses arabic labels for vendor bill documents navigation', function (): void {
    app()->setLocale('ar');

    expect(ManageBillDocuments::getNavigationLabel())->toBe('المستندات');
});

it('uses arabic plural label for vendor bills resource', function (): void {
    app()->setLocale('ar');

    expect(BillResource::getPluralModelLabel())->toBe('فواتير الموردين');
});

it('uses arabic cluster breadcrumb for invoices vendors cluster', function (): void {
    app()->setLocale('ar');

    expect(Vendors::getClusterBreadcrumb())->toBe('الموردون');
});

it('loads bill resource override from the app layer', function (): void {
    expect((new ReflectionClass(BillResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Invoice');
});

it('registers documents relationship on vendor bills', function (): void {
    if (! Schema::hasTable('invoice_attachments')) {
        test()->markTestSkipped('invoice_attachments table is not available.');
    }

    $move = Move::factory()->vendorBill()->create();
    $bill = Bill::find($move->id);

    expect($bill)->not->toBeNull()
        ->and($bill->documents())->toBeInstanceOf(HasMany::class)
        ->and($bill->documents()->getModel())->toBeInstanceOf(InvoiceAttachment::class);
});

it('stores vendor bill attachments on the private disk', function (): void {
    if (! Schema::hasTable('invoice_attachments')) {
        test()->markTestSkipped('invoice_attachments table is not available.');
    }

    Storage::fake('private');

    $move = Move::factory()->vendorBill()->create();
    $path = 'invoices/vendor-documents/2026/test.pdf';

    Storage::disk('private')->put($path, 'pdf-content');

    $attachment = InvoiceAttachment::query()->create([
        'invoice_id' => $move->id,
        'file_path'  => $path,
        'file_name'  => 'test.pdf',
        'file_size'  => 11,
        'mime_type'  => 'application/pdf',
        'title'      => 'فاتورة مورد',
    ]);

    expect($attachment->isPreviewable())->toBeTrue()
        ->and(Storage::disk('private')->exists($path))->toBeTrue()
        ->and($move->documents()->count())->toBe(1);

    $attachment->delete();
});

it('registers documents page on bill resource', function (): void {
    $pages = BillResource::getPages();

    expect($pages)->toHaveKey('documents')
        ->and($pages['documents']->getPage())->toBe(ManageBillDocuments::class);
});

it('includes manage bill documents page in resource pages', function (): void {
    $pageClasses = collect(BillResource::getPages())
        ->map(fn ($route) => $route->getPage())
        ->all();

    expect($pageClasses)->toContain(ManageBillDocuments::class);
});
