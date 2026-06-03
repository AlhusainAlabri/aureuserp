<?php

namespace App\Filament\Inventory\Concerns;

use App\Support\FilamentUrl;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReceiptResource;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\PurchaseOrder;

trait InteractsWithPendingReceiptCount
{
    protected function countPendingReceipts(): int
    {
        if (! Schema::hasTable('purchases_orders') || ! Schema::hasTable('purchases_order_operations')) {
            return 0;
        }

        return PurchaseOrder::query()
            ->where('state', OrderState::PURCHASE)
            ->where(function ($query): void {
                $query->whereDoesntHave('operations')
                    ->orWhereHas('operations', fn ($operations) => $operations->whereNotIn('state', [
                        OperationState::DONE->value,
                        OperationState::CANCELED->value,
                    ]));
            })
            ->count();
    }

    protected function pendingReceiptsUrl(): string
    {
        return FilamentUrl::appendLocaleToUrl(
            ReceiptResource::getUrl('index', FilamentUrl::withLocale([
                'activeTableView' => 'todo_receipts',
            ])),
        );
    }
}
