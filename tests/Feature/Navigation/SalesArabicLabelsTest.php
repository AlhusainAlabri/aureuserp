<?php

use App\Filament\Sales\Pages\ListOrders;
use App\Filament\Sales\Pages\ListQuotations;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;

it('uses arabic plural labels for orders without polluting payroll resources', function (): void {
    app()->setLocale('ar');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect(OrderResource::getPluralModelLabel())->toBe('الطلبات')
        ->and(PayrollBatchResource::getPluralModelLabel())->toBe('دفعات الرواتب')
        ->and(app(ListQuotations::class)->getTitle())->toBe('عروض الأسعار');
});

it('loads sales list page overrides from the app layer', function (): void {
    expect((new ReflectionClass(ListQuotations::class))->getFileName())
        ->toContain('app/Filament/Sales/Pages/ListQuotations.php')
        ->and((new ReflectionClass(ListOrders::class))->getFileName())
        ->toContain('app/Filament/Sales/Pages/ListOrders.php');
});

it('uses arabic labels for activity type navigation', function (): void {
    app()->setLocale('ar');

    expect(ActivityTypeResource::getNavigationGroup())->toBe(__('Activities'))
        ->and(ActivityTypeResource::getModelLabel())->toBe(__('Activity Type'));
});
