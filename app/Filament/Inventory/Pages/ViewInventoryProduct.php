<?php

namespace App\Filament\Inventory\Pages;

use App\Filament\Inventory\Concerns\InteractsWithProductPurchaseHistoryTab;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages\ViewProduct as BaseViewProduct;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class ViewInventoryProduct extends BaseViewProduct
{
    use HasRecordNavigationTabs {
        InteractsWithProductPurchaseHistoryTab::getRecordNavigationTabsWidget insteadof HasRecordNavigationTabs;
    }
    use InteractsWithProductPurchaseHistoryTab;
}
