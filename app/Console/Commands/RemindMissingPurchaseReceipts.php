<?php

namespace App\Console\Commands;

use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\PurchaseOrder;

class RemindMissingPurchaseReceipts extends Command
{
    protected $signature = 'purchases:remind-receipts';

    protected $description = 'Send reminders for purchase orders with missing receipts';

    public function handle(): void
    {
        $orders = PurchaseOrder::query()
            ->whereIn('state', [OrderState::PURCHASE->value, OrderState::DONE->value])
            ->where('receipt_uploaded', false)
            ->where(function ($query) {
                $query->whereNull('receipt_reminder_sent_at')
                    ->orWhere('receipt_reminder_sent_at', '<', now()->subDays(3));
            })
            ->with('creator')
            ->get();

        foreach ($orders as $order) {
            if ($order->creator) {
                Notification::make()
                    ->title(__('purchases::filament/admin/clusters/orders/resources/order.notifications.receipt-required.title'))
                    ->body(__('purchases::filament/admin/clusters/orders/resources/order.notifications.receipt-required.body', [
                        'reference' => $order->name,
                    ]))
                    ->warning()
                    ->sendToDatabase($order->creator);
            }

            $order->update(['receipt_reminder_sent_at' => now()]);
        }

        $this->info("Sent {$orders->count()} receipt reminders.");
    }
}
