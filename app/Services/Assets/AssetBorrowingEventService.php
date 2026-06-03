<?php

namespace App\Services\Assets;

use App\Models\Assets\AssetBorrowingEvent;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Models\AssetBorrowing;

class AssetBorrowingEventService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(AssetBorrowing $borrowing, string $eventType, ?array $payload = null): ?AssetBorrowingEvent
    {
        if (! Schema::hasTable('asset_borrowing_events')) {
            return null;
        }

        return AssetBorrowingEvent::query()->create([
            'asset_borrowing_id' => $borrowing->id,
            'asset_id'           => $borrowing->asset_id,
            'event_type'         => $eventType,
            'actor_id'           => auth()->id(),
            'ip_address'         => Request::ip(),
            'user_agent'         => Request::userAgent(),
            'payload'            => $payload,
            'created_at'         => now(),
        ]);
    }
}
