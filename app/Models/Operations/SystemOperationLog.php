<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

class SystemOperationLog extends Model
{
    protected $fillable = [
        'action',
        'subject_type',
        'subject_id',
        'user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(string $action, ?Model $subject = null, ?User $user = null, array $metadata = []): ?self
    {
        if (! Schema::hasTable('system_operation_logs')) {
            return null;
        }

        return static::query()->create([
            'action'       => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'user_id'      => $user?->getKey() ?? auth()->id(),
            'metadata'     => $metadata === [] ? null : $metadata,
        ]);
    }
}
