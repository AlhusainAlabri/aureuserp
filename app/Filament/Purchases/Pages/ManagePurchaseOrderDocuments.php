<?php

namespace App\Filament\Purchases\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;

class ManagePurchaseOrderDocuments extends ManagePurchaseDocuments
{
    protected static string $resource = PurchaseOrderResource::class;
}
