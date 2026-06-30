<?php

use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Webkul\Account\Models\PaymentTerm;
use Webkul\Purchase\Filament\Admin\Clusters\Orders;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation;

it('uses arabic plural labels and cluster breadcrumb for purchase quotations', function (): void {
    app()->setLocale('ar');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect(QuotationResource::getPluralModelLabel())->toBe('طلبات عروض الأسعار')
        ->and(QuotationResource::getModelLabel())->toBe('طلب عرض أسعار')
        ->and(Orders::getClusterBreadcrumb())->toBe('الطلبات');
});

it('loads purchase view quotation override from the app layer', function (): void {
    expect((new ReflectionClass(ViewQuotation::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Purchase/Filament/Admin/Clusters/Orders/Resources/QuotationResource/Pages/ViewQuotation.php');
});

it('localizes seeded payment term names when locale is arabic', function (): void {
    if (! PaymentTerm::query()->exists()) {
        $this->markTestSkipped('Payment terms are not seeded.');
    }

    app()->setLocale('ar');

    $term = PaymentTerm::query()->find(10);

    expect($term)->not->toBeNull()
        ->and($term->name)->toBe('90 يوماً، في العاشر');
});
