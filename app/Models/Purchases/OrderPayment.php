<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Purchase\Models\Order;

class OrderPayment extends Model
{
    protected $table = 'purchases_order_payments';

    protected $fillable = [
        'order_id',
        'amount',
        'paid_at',
        'voucher_path',
        'notes',
        'recorded_by',
        'account_move_id',
    ];

    protected $casts = [
        'amount'  => 'decimal:4',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
