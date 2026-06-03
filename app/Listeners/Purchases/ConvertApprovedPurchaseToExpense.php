<?php

namespace App\Listeners\Purchases;

use App\Services\Purchases\PurchaseExpenseConversionService;
use Webkul\Purchase\Models\PurchaseOrder;
use Wezlo\FilamentApproval\Events\ApprovalCompleted;

class ConvertApprovedPurchaseToExpense
{
    public function __construct(
        private readonly PurchaseExpenseConversionService $conversionService,
    ) {}

    public function handle(ApprovalCompleted $event): void
    {
        $approvable = $event->approval->approvable;

        if (! $approvable instanceof PurchaseOrder) {
            return;
        }

        $this->conversionService->convertIfEligible($approvable);
    }
}
