<?php

namespace App\Models\Operations;

use App\Enums\Operations\HealthStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class SystemHealthSnapshot extends Model
{
    protected $fillable = [
        'overall_status',
        'checks',
        'summary',
        'triggered_by',
        'triggered_by_user_id',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_status' => HealthStatus::class,
            'checks'         => 'array',
            'summary'        => 'array',
            'checked_at'     => 'datetime',
        ];
    }

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
