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
            if (
                $order->isDirty('state')
                && in_array($order->state, [OrderState::PURCHASE->value, OrderState::DONE->value])
                && ! $order->isApproved()
            ) {
                throw new \RuntimeException(
                    __('purchases::models/purchase-order.approval.required')
                );
            }
        });
    }
}
