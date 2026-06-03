<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Security\Models\User;

class AssetBorrowingEvent extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new \RuntimeException(__('assets-extensions::audit.immutable')));
        static::deleting(fn () => throw new \RuntimeException(__('assets-extensions::audit.immutable')));
    }

    protected $fillable = [
        'asset_borrowing_id',
        'asset_id',
        'event_type',
        'actor_id',
        'ip_address',
        'user_agent',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(AssetBorrowing::class, 'asset_borrowing_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
