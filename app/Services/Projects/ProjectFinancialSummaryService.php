<?php

namespace App\Services\Projects;

use Illuminate\Support\Facades\Schema;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Models\Move;
use Webkul\Project\Models\Project;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;

class ProjectFinancialSummaryService
{
    /**
     * @return array{purchase_total: float, invoice_total: float, grand_total: float}
     */
    public function summarize(Project $project): array
    {
        $purchaseTotal = $this->purchaseTotal($project);
        $invoiceTotal = $this->invoiceTotal($project);

        return [
            'purchase_total' => $purchaseTotal,
            'invoice_total'  => $invoiceTotal,
            'grand_total'    => round($purchaseTotal + $invoiceTotal, 3),
        ];
    }

    public function formatOmr(float $amount): string
    {
        $formatted = number_format($amount, 3);

        return app()->getLocale() === 'ar'
            ? 'ر.ع. '.$formatted
            : 'OMR '.$formatted;
    }

    protected function purchaseTotal(Project $project): float
    {
        if (! Schema::hasTable('purchases_orders') || ! Schema::hasColumn('purchases_orders', 'project_id')) {
            return 0.0;
        }

        return (float) Order::query()
            ->where('project_id', $project->id)
            ->whereIn('state', [OrderState::PURCHASE->value, OrderState::DONE->value])
            ->sum('total_amount');
    }

    protected function invoiceTotal(Project $project): float
    {
        if (! Schema::hasTable('accounts_account_moves') || ! Schema::hasColumn('accounts_account_moves', 'project_id')) {
            return 0.0;
        }

        return (float) Move::query()
            ->where('project_id', $project->id)
            ->where('state', MoveState::POSTED->value)
            ->whereIn('move_type', [
                MoveType::IN_INVOICE->value,
                MoveType::OUT_INVOICE->value,
            ])
            ->sum('amount_total');
    }
}
