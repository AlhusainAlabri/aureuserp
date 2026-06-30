<?php

use App\Filament\Invoices\Pages\ManageInvoiceDocuments;
use App\Models\Invoices\InvoiceAttachment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Webkul\Account\Models\Move;
use Webkul\Invoice\Filament\Clusters\Customers;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource;
use Webkul\Invoice\Models\Invoice;

it('uses arabic labels for invoice documents navigation', function (): void {
    app()->setLocale('ar');

    expect(ManageInvoiceDocuments::getNavigationLabel())->toBe('المستندات');
});

it('uses arabic plural label for invoices resource', function (): void {
    app()->setLocale('ar');

    expect(InvoiceResource::getPluralModelLabel())->toBe('الفواتير');
});

it('uses arabic cluster breadcrumb for invoices customers cluster', function (): void {
    app()->setLocale('ar');

    expect(Customers::getClusterBreadcrumb())->toBe('العملاء');
});

it('loads invoice resource override from the app layer', function (): void {
    expect((new ReflectionClass(InvoiceResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Invoice');
});

it('registers documents relationship on invoices', function (): void {
    if (! Schema::hasTable('invoice_attachments')) {
        test()->markTestSkipped('invoice_attachments table is not available.');
    }

    $move = Move::factory()->invoice()->create();
    $invoice = Invoice::find($move->id);

    expect($invoice)->not->toBeNull()
        ->and($invoice->documents())->toBeInstanceOf(HasMany::class)
        ->and($invoice->documents()->getModel())->toBeInstanceOf(InvoiceAttachment::class);
});

it('stores invoice attachments on the private disk', function (): void {
    if (! Schema::hasTable('invoice_attachments')) {
        test()->markTestSkipped('invoice_attachments table is not available.');
    }

    Storage::fake('private');

    $move = Move::factory()->invoice()->create();
    $path = 'invoices/documents/2026/test.pdf';

    Storage::disk('private')->put($path, 'pdf-content');

    $attachment = InvoiceAttachment::query()->create([
        'invoice_id' => $move->id,
        'file_path'  => $path,
        'file_name'  => 'test.pdf',
        'file_size'  => 11,
        'mime_type'  => 'application/pdf',
        'title'      => 'فاتورة موقعة',
    ]);

    expect($attachment->isPreviewable())->toBeTrue()
        ->and(Storage::disk('private')->exists($path))->toBeTrue()
        ->and($move->documents()->count())->toBe(1);

    $attachment->delete();
});

it('registers documents page on invoice resource', function (): void {
    $pages = InvoiceResource::getPages();

    expect($pages)->toHaveKey('documents')
        ->and($pages['documents']->getPage())->toBe(ManageInvoiceDocuments::class);
});

it('includes manage invoice documents page in resource pages', function (): void {
    $pageClasses = collect(InvoiceResource::getPages())
        ->map(fn ($route) => $route->getPage())
        ->all();

    expect($pageClasses)->toContain(ManageInvoiceDocuments::class);
});
