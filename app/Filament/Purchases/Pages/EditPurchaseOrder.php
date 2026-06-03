<?php

namespace App\Filament\Purchases\Pages;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Services\Purchases\InternalRequestLineService;
use Webkul\Purchase\Facades\PurchaseOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\EditPurchaseOrder as BaseEditPurchaseOrder;
use Webkul\Purchase\Models\PurchaseOrder as PurchaseOrderModel;

class EditPurchaseOrder extends BaseEditPurchaseOrder
{
    /** @var array<int, array<string, mixed>> */
    protected array $pendingInternalLineItems = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (
            $record instanceof PurchaseOrderModel
            && PurchaseOrderResourceExtensions::isInternalRequest($record->request_type)
        ) {
            $data['internal_line_items'] = app(InternalRequestLineService::class)
                ->formStateFromOrder($record);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingInternalLineItems = $data['internal_line_items'] ?? [];
        unset($data['internal_line_items']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if (
            $record instanceof PurchaseOrderModel
            && PurchaseOrderResourceExtensions::isInternalRequest($record->request_type)
        ) {
            app(InternalRequestLineService::class)->syncFromFormData(
                $record,
                $this->pendingInternalLineItems,
            );

            PurchaseOrder::computePurchaseOrder($record->fresh());
        }
    }
}
