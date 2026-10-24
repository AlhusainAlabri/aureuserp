<?php

namespace Webkul\Account\Filament\Resources;

class ProductResource extends CoreProductResource
{
    public static function getModelLabel(): string
    {
        return static::translatedResourceLabel('singular') ?? __('admin.quick_create.items.product');
    }

    public static function getPluralModelLabel(): string
    {
        return static::translatedResourceLabel('plural') ?? __('invoices::filament/clusters/vendors/resources/product.navigation.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/vendors/resources/product.navigation.title');
    }
}
