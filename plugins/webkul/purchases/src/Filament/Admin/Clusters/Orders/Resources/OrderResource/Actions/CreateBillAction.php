<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Webkul\Account\Models\Move;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Facades\PurchaseOrder;
use Webkul\Purchase\Models\Order;

class CreateBillAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.create-bill';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.label'))
            ->color(function (Order $record): string {
                if ($record->qty_to_invoice == 0) {
                    return 'gray';
                }

                return 'primary';
            })
            ->action(function (Order $record, Component $livewire): void {
                if ($record->qty_to_invoice == 0) {
                    Notification::make()
                        ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.action.notification.warning.title'))
                        ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.action.notification.warning.body'))
                        ->warning()
                        ->send();

                    return;
                }

                $record = PurchaseOrder::createPurchaseOrderBill($record);

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.action.notification.success.title'))
                    ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            // Creating a bill is an accounting action: a buyer without bill permission must not get it from the order page.
            ->visible(fn () => in_array($this->getRecord()->state, [
                OrderState::PURCHASE,
                OrderState::DONE,
            ]) && (bool) Auth::user()?->can('create', Move::class));
    }
}
