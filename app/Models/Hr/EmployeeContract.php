<?php

namespace App\Models\Hr;

use App\Enums\Hr\ContractType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class EmployeeContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'contract_type',
        'start_date',
        'end_date',
        'renewal_date',
        'first_joining_date',
        'wage',
        'contract_file_path',
        'notes',
        'is_active',
        'notified_at',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'contract_type'      => ContractType::class,
            'start_date'         => 'date',
            'end_date'           => 'date',
            'renewal_date'       => 'date',
            'first_joining_date' => 'date',
            'wage'               => 'decimal:3',
            'is_active'          => 'boolean',
            'notified_at'        => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiringWithin(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('end_date')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function contractTemporaryUrl(): ?string
    {
        if (! $this->contract_file_path) {
            return null;
        }

        return Storage::disk('private')->temporaryUrl($this->contract_file_path, now()->addMinutes(60));
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $contract): void {
            $contract->creator_id ??= Auth::id();
        });
    }
}
