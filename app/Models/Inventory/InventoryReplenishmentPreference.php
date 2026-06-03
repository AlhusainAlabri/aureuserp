<?php

namespace App\Models\Inventory;

use App\Enums\Inventory\DefaultProcurement;
use App\Enums\Purchases\RequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Models\OrderPoint;

class InventoryReplenishmentPreference extends Model
{
    protected $fillable = [
        'order_point_id',
        'default_procurement',
        'default_request_type',
    ];

    protected function casts(): array
    {
        return [
            'default_procurement'  => DefaultProcurement::class,
            'default_request_type' => RequestType::class,
        ];
    }

    public function orderPoint(): BelongsTo
    {
        return $this->belongsTo(OrderPoint::class, 'order_point_id');
    }
}
