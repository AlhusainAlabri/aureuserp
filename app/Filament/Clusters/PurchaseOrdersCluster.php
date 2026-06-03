<?php

namespace App\Filament\Clusters;

use Webkul\Purchase\Filament\Admin\Clusters\Orders as BaseOrdersCluster;

class PurchaseOrdersCluster extends BaseOrdersCluster
{
    public static function getClusterBreadcrumb(): ?string
    {
        return __('purchases::filament/admin/clusters/orders.navigation.title');
    }
}
