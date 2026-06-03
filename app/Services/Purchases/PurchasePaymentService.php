<?php

namespace App\Services\Purchases;

use App\Models\Purchases\OrderPayment;
use Illuminate\Support\Facades\Auth;
use Webkul\Purchase\Models\Order;

class PurchasePaymentService
{
    public function syncAmounts(Order $order): void
    {
        $totalPaid = (float) $order->payments()->sum('amount');
        $totalAmount = (float) ($order->total_amount ?? 0);

        $order->update([
            'amount_paid'      => $totalPaid,
            'amount_remaining' => max(0, $totalAmount - $totalPaid),
        ]);
    }

    /**
     * @param  array{amount: float|string, paid_at?: string|null, voucher_path?: string|null, notes?: string|null, account_move_id?: int|null}  $data
     */
    public function recordPayment(Order $order, array $data): OrderPayment
    {
        if (empty($data['account_move_id']) && $order->accountMoves()->exists()) {
            $data['account_move_id'] = $order->accountMoves()->latest('id')->value('id');
        }

        $payment = $order->payments()->create([
            'amount'          => $data['amount'],
            'paid_at'         => $data['paid_at'] ?? now(),
            'voucher_path'    => $data['voucher_path'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'recorded_by'     => Auth::id(),
            'account_move_id' => $data['account_move_id'] ?? null,
        ]);

        $this->syncAmounts($order->fresh());

        return $payment;
    }

    public function storeFinanceVoucher(Order $order, string $path): void
    {
        $order->update([
            'payment_voucher_path'        => $path,
            'payment_voucher_uploaded_at' => now(),
        ]);
    }
}
