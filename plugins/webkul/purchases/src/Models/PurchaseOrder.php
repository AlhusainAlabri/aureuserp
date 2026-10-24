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

            $confirmedStates = [OrderState::PURCHASE->value, OrderState::DONE->value];

            $originalState = $order->getOriginal('state');
            $originalState = $originalState instanceof OrderState ? $originalState->value : $originalState;

            // Approval is required to *enter* a confirmed state; locking or unlocking an
            // already confirmed order (purchase <-> done) must not ask for it again.
            if (
                $order->isDirty('state')
                && in_array($state, $confirmedStates, true)
                && ! in_array($originalState, $confirmedStates, true)
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
