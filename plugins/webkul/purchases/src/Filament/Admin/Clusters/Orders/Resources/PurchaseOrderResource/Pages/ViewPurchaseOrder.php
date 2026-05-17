<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use App\Filament\Traits\HasApprovalActions;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;

class ViewPurchaseOrder extends ViewOrder
{
    use HasApprovalActions;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(
            parent::getHeaderActions(),
            $this->getApprovalActions()
        );
    }
}
