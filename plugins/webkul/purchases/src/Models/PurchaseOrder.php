<?php

namespace Webkul\Purchase\Models;

use App\Traits\HasApprovalWorkflow;
use Webkul\Purchase\Enums\OrderState;

class PurchaseOrder extends Order
{
    use HasApprovalWorkflow;

    public function getModelTitle(): string
    {
        return __('purchases::models/purchase-order.title');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function (self $order) {
            $state = $order->state instanceof OrderState
                ? $order->state->value
                : $order->state;

            if (
                $order->isDirty('state')
                && in_array($state, [OrderState::PURCHASE->value, OrderState::DONE->value], true)
                && $order->hasActiveApprovalFlow()
                && ! $order->isApproved()
            ) {
                throw new \RuntimeException(
                    __('purchases::models/purchase-order.approval.required')
                );
            }
        });
    }
}
