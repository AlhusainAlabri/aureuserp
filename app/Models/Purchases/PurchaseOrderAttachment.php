<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Purchase\Models\Order;
use Webkul\Security\Models\User;

class PurchaseOrderAttachment extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'title',
        'notes',
        'creator_id',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ], true);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $attachment): void {
            $attachment->creator_id ??= Auth::id();
        });
    }
}
