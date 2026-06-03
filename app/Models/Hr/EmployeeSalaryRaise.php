<?php

namespace App\Models\Hr;

use App\Enums\Hr\RaiseReason;
use App\Services\Hr\SalaryRaiseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class EmployeeSalaryRaise extends Model
{
    protected $fillable = [
        'employee_id',
        'contract_id',
        'effective_date',
        'old_amount',
        'new_amount',
        'raise_amount',
        'raise_percent',
        'reason',
        'notes',
        'approved_by',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'old_amount'     => 'decimal:3',
            'new_amount'     => 'decimal:3',
            'raise_amount'   => 'decimal:3',
            'raise_percent'  => 'decimal:2',
            'reason'         => RaiseReason::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $raise): void {
            $raise->creator_id ??= Auth::id();

            $oldAmount = (float) $raise->old_amount;
            $newAmount = (float) $raise->new_amount;
            $raise->raise_amount ??= round($newAmount - $oldAmount, 3);
            $raise->raise_percent ??= $oldAmount > 0
                ? round(($raise->raise_amount / $oldAmount) * 100, 2)
                : 0;
        });

        static::created(function (self $raise): void {
            app(SalaryRaiseService::class)->applyRaise($raise);
        });
    }
}
