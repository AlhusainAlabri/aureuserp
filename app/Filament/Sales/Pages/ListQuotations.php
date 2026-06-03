<?php

namespace App\Filament\Sales\Pages;

use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ListQuotations as BaseListQuotations;

class ListQuotations extends BaseListQuotations
{
    public function getTitle(): string|Htmlable
    {
        return __('sales::filament/clusters/orders/resources/quotation.navigation.title');
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyStateHeading(__('sales-extensions::empty.quotations.heading'))
            ->emptyStateDescription(__('sales-extensions::empty.quotations.description'));
    }
}
