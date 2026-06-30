<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Sale\Models\Order;
use Webkul\Security\Models\User;

class SalesOrderAttachment extends Model
{
    protected $fillable = [
        'sales_order_id',
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
        return $this->belongsTo(Order::class, 'sales_order_id');
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

        static::deleting(function (self $attachment): void {
            if ($attachment->file_path) {
                Storage::disk('private')->delete($attachment->file_path);
            }
        });
    }
}
