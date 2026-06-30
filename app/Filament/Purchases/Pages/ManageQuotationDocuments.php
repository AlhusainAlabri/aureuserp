<?php

namespace App\Filament\Purchases\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource;

class ManageQuotationDocuments extends ManagePurchaseDocuments
{
    protected static string $resource = QuotationResource::class;
}
