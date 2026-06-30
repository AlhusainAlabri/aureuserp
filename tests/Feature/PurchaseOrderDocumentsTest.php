<?php

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Filament\Purchases\Pages\ManagePurchaseOrderDocuments;
use App\Filament\Purchases\Pages\ManageQuotationDocuments;
use App\Models\Purchases\PurchaseOrderAttachment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Models\PurchaseOrder;

it('uses arabic labels for purchase documents navigation', function (): void {
    app()->setLocale('ar');

    expect(ManagePurchaseOrderDocuments::getNavigationLabel())->toBe('المستندات')
        ->and(ManageQuotationDocuments::getNavigationLabel())->toBe('المستندات');
});

it('registers documents relationship on purchase orders', function (): void {
    if (! Schema::hasTable('purchase_order_attachments')) {
        test()->markTestSkipped('purchase_order_attachments table is not available.');
    }

    $order = PurchaseOrder::query()->first() ?? Order::query()->first();

    if (! $order) {
        test()->markTestSkipped('No purchase orders available for relationship test.');
    }

    expect($order->documents())->toBeInstanceOf(HasMany::class)
        ->and($order->documents()->getModel())->toBeInstanceOf(PurchaseOrderAttachment::class);
});

it('stores purchase order attachments on the private disk', function (): void {
    if (! Schema::hasTable('purchase_order_attachments')) {
        test()->markTestSkipped('purchase_order_attachments table is not available.');
    }

    Storage::fake('private');

    $order = PurchaseOrder::query()->first() ?? Order::query()->first();

    if (! $order) {
        test()->markTestSkipped('No purchase orders available for attachment test.');
    }

    $path = 'purchases/documents/2026/test.pdf';

    Storage::disk('private')->put($path, 'pdf-content');

    $attachment = PurchaseOrderAttachment::query()->create([
        'purchase_order_id' => $order->id,
        'file_path'         => $path,
        'file_name'         => 'test.pdf',
        'file_size'         => 11,
        'mime_type'         => 'application/pdf',
        'title'             => 'عرض سعر',
    ]);

    expect($attachment->isPreviewable())->toBeTrue()
        ->and(Storage::disk('private')->exists($path))->toBeTrue()
        ->and($order->documents()->count())->toBeGreaterThan(0);

    $attachment->delete();
});

it('loads purchase order resource override from the app layer', function (): void {
    expect((new ReflectionClass(PurchaseOrderResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Purchase');
});

it('formats purchase order amounts in omr for arabic locale', function (): void {
    app()->setLocale('ar');

    expect(PurchaseOrderResourceExtensions::formatOmrAmount(1.5))
        ->toBe('ر.ع. 1.500');
});
