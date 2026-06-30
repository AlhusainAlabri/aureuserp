<?php

namespace App\Models\Invoices;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Models\Move;
use Webkul\Security\Models\User;

class InvoiceAttachment extends Model
{
    protected $fillable = [
        'invoice_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'title',
        'notes',
        'creator_id',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Move::class, 'invoice_id');
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
