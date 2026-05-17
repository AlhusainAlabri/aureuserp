<?php

namespace Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages;

use App\Filament\Traits\HasApprovalActions;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ViewInvoice as BaseViewInvoice;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\CreditNoteResource;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource;

class ViewInvoice extends BaseViewInvoice
{
    use HasApprovalActions;

    protected static string $resource = InvoiceResource::class;

    protected static string $reverseResource = CreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(parent::getHeaderActions(), $this->getApprovalActions());
    }
}
