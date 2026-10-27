<?php

use Webkul\Account\Filament\Resources\ProductResource as AccountProductResource;
use Webkul\Account\Filament\Resources\VendorResource as AccountVendorResource;
use Webkul\Account\Filament\Resources\VendorResource\Pages\ListVendors as AccountListVendors;
use Webkul\Accounting\Filament\Clusters\Vendors\Resources\ProductResource as AccountingProductResource;
use Webkul\Accounting\Filament\Clusters\Vendors\Resources\VendorResource as AccountingVendorResource;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\ProductResource as InvoiceCustomerProductResource;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource as InvoiceVendorProductResource;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource\Pages\ListProducts as InvoiceListProducts;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource as InvoiceVendorResource;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages\ListVendors as InvoiceListVendors;

it('uses arabic labels for invoice vendor products resource', function (): void {
    app()->setLocale('ar');

    expect(InvoiceVendorProductResource::getNavigationLabel())->toBe('المنتجات')
        ->and(InvoiceVendorProductResource::getPluralModelLabel())->toBe('المنتجات')
        ->and(InvoiceVendorProductResource::getModelLabel())->toBe('منتج');
});

it('uses arabic labels for invoice vendors resource', function (): void {
    app()->setLocale('ar');

    expect(InvoiceVendorResource::getNavigationLabel())->toBe('الموردين')
        ->and(InvoiceVendorResource::getPluralModelLabel())->toBe('الموردين')
        ->and(InvoiceVendorResource::getModelLabel())->toBe('مورد');
});

it('uses arabic plural label for invoice customer products resource', function (): void {
    app()->setLocale('ar');

    expect(InvoiceCustomerProductResource::getNavigationLabel())->toBe('المنتجات')
        ->and(InvoiceCustomerProductResource::getPluralModelLabel())->toBe('المنتجات');
});

it('uses arabic labels for accounting vendors and products resources', function (): void {
    app()->setLocale('ar');

    expect(AccountingProductResource::getNavigationLabel())->toBe('منتجات الموردين')
        ->and(AccountingProductResource::getPluralModelLabel())->toBe('المنتجات')
        ->and(AccountingVendorResource::getNavigationLabel())->toBe('الموردين')
        ->and(AccountingVendorResource::getPluralModelLabel())->toBe('الموردين');
});

it('uses arabic titles for invoice vendors and products list pages', function (): void {
    app()->setLocale('ar');

    expect(app(InvoiceListProducts::class)->getTitle())->toBe('المنتجات')
        ->and(app(InvoiceListVendors::class)->getTitle())->toBe('الموردين');
});

it('loads vendor and product resource overrides from the app layer', function (): void {
    expect((new ReflectionClass(AccountVendorResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Account')
        ->and((new ReflectionClass(AccountListVendors::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Account')
        ->and((new ReflectionClass(AccountProductResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Account');
});
