<?php

namespace App\Filament\Purchases\Pages;

use App\Enums\Purchases\RequestType;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Services\Purchases\InternalRequestLineService;
use Webkul\Purchase\Facades\PurchaseOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\CreatePurchaseOrder as BaseCreatePurchaseOrder;
use Webkul\Purchase\Models\PurchaseOrder as PurchaseOrderModel;

class CreatePurchaseOrder extends BaseCreatePurchaseOrder
{
    /** @var array<int, array<string, mixed>> */
    protected array $pendingInternalLineItems = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingInternalLineItems = $data['internal_line_items'] ?? [];
        unset($data['internal_line_items']);

        return $data;
    }

    protected function afterCreate(): void
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

        parent::afterCreate();
    }

    public function getTitle(): string
    {
        return $this->resolveInternalRequestTitle() ?? parent::getTitle();
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    protected function resolveInternalRequestTitle(): ?string
    {
        $requestType = request()->query('request_type');

        if (! is_string($requestType) || ! in_array($requestType, RequestType::internalRequestTypes(), true)) {
            return null;
        }

        $type = RequestType::from($requestType);

        return __('purchases-extensions::request.create_title', [
            'type' => $type->getLabel(),
        ]);
    }
}
