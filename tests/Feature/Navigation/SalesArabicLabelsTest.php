<?php

use App\Filament\Sales\Pages\ListOrders;
use App\Filament\Sales\Pages\ListQuotations;
use App\Filament\Sales\Pages\ManageSalesOrderDocuments;
use App\Models\Sales\SalesOrderAttachment;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource;
use Webkul\Sale\Filament\Clusters\Orders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ManageInvoices;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation;
use Webkul\Sale\Models\Order;

it('uses arabic plural labels for quotations and orders without polluting payroll resources', function (): void {
    app()->setLocale('ar');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect(QuotationResource::getPluralModelLabel())->toBe('عروض الأسعار')
        ->and(OrderResource::getPluralModelLabel())->toBe('الطلبات')
        ->and(PayrollBatchResource::getPluralModelLabel())->toBe('دفعات الرواتب')
        ->and(app(ListQuotations::class)->getTitle())->toBe('عروض الأسعار')
        ->and(Orders::getClusterBreadcrumb())->toBe('الطلبات')
        ->and(ManageInvoices::getNavigationLabel())->toBe('الفواتير')
        ->and(ManageSalesOrderDocuments::getNavigationLabel())->toBe('المستندات');
});

it('loads sales list page overrides from the app layer', function (): void {
    expect((new ReflectionClass(ListQuotations::class))->getFileName())
        ->toContain('app/Filament/Sales/Pages/ListQuotations.php')
        ->and((new ReflectionClass(ListOrders::class))->getFileName())
        ->toContain('app/Filament/Sales/Pages/ListOrders.php');
});

it('loads sales view quotation override from the app layer', function (): void {
    expect((new ReflectionClass(ViewQuotation::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/QuotationResource/Pages/ViewQuotation.php');
});

it('uses arabic labels for activity type navigation', function (): void {
    app()->setLocale('ar');

    expect(ActivityTypeResource::getNavigationGroup())->toBe(__('Activities'))
        ->and(ActivityTypeResource::getModelLabel())->toBe(__('Activity Type'));
});

it('registers documents relationship on sales orders', function (): void {
    $quotation = Order::factory()->create();

    expect($quotation->documents()->getModel())->toBeInstanceOf(SalesOrderAttachment::class);
});

it('stores sales order attachments on the private disk', function (): void {
    Storage::fake('private');

    $quotation = Order::factory()->create();
    $path = 'sales/2026/test.pdf';

    Storage::disk('private')->put($path, 'pdf-content');

    $attachment = SalesOrderAttachment::query()->create([
        'sales_order_id' => $quotation->id,
        'file_path'      => $path,
        'file_name'      => 'test.pdf',
        'file_size'      => 11,
        'mime_type'      => 'application/pdf',
        'title'          => 'Test Contract',
    ]);

    expect($attachment->isPreviewable())->toBeTrue()
        ->and(Storage::disk('private')->exists($path))->toBeTrue();

    $attachment->delete();

    expect(Storage::disk('private')->exists($path))->toBeFalse();
});
