<?php

use Webkul\Account\Filament\Resources\CustomerResource as AccountCustomerResource;
use Webkul\Account\Filament\Resources\CustomerResource\Pages\ListCustomers as AccountListCustomers;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\CustomerResource as AccountingCustomerResource;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\CustomerResource as InvoiceCustomerResource;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\CustomerResource\Pages\ListCustomers as InvoiceListCustomers;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource as SalesCustomerResource;

it('uses arabic labels for invoice customers resource', function (): void {
    app()->setLocale('ar');

    expect(InvoiceCustomerResource::getNavigationLabel())->toBe('العملاء')
        ->and(InvoiceCustomerResource::getPluralModelLabel())->toBe('العملاء')
        ->and(InvoiceCustomerResource::getModelLabel())->toBe('عميل');
});

it('uses arabic labels for accounting customers resource', function (): void {
    app()->setLocale('ar');

    expect(AccountingCustomerResource::getNavigationLabel())->toBe('العملاء')
        ->and(AccountingCustomerResource::getPluralModelLabel())->toBe('العملاء')
        ->and(AccountingCustomerResource::getModelLabel())->toBe('عميل');
});

it('uses arabic plural label for sales customers resource', function (): void {
    app()->setLocale('ar');

    expect(SalesCustomerResource::getNavigationLabel())->toBe('العملاء')
        ->and(SalesCustomerResource::getPluralModelLabel())->toBe('العملاء');
});

it('uses arabic title for invoice customers list page', function (): void {
    app()->setLocale('ar');

    $page = app(InvoiceListCustomers::class);

    expect($page->getTitle())->toBe('العملاء');
});

it('loads customer resource overrides from the app layer', function (): void {
    expect((new ReflectionClass(AccountCustomerResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Account')
        ->and((new ReflectionClass(AccountListCustomers::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Account');
});
