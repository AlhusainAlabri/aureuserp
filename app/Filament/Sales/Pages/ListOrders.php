<?php

namespace App\Filament\Sales\Pages;

use Filament\Tables\Table;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\ListOrders as BaseListOrders;

class ListOrders extends BaseListOrders
{
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyStateHeading(__('sales-extensions::empty.orders.heading'))
            ->emptyStateDescription(__('sales-extensions::empty.orders.description'));
    }
}
