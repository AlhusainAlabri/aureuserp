<?php

namespace Webkul\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class EndOfServiceSettlement extends Model
{
    use SoftDeletes;

    protected $table = 'payroll_end_of_service_settlements';

    protected $fillable = [
        'employee_id',
        'company_id',
        'join_date',
        'departure_date',
        'service_days',
        'last_basic',
        'gratuity_breakdown',
        'gratuity_amount',
        'unused_leave_days',
        'leave_payout_amount',
        'loan_offset_amount',
        'final_salary_amount',
        'other_adjustment',
        'adjustment_note',
        'net_amount',
        'status',
        'approved_by',
        'approved_at',
        'paid_at',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'join_date'           => 'date',
            'departure_date'      => 'date',
            'service_days'        => 'integer',
            'last_basic'          => 'decimal:3',
            'gratuity_breakdown'  => 'array',
            'gratuity_amount'     => 'decimal:3',
            'unused_leave_days'   => 'decimal:2',
            'leave_payout_amount' => 'decimal:3',
            'loan_offset_amount'  => 'decimal:3',
            'final_salary_amount' => 'decimal:3',
            'other_adjustment'    => 'decimal:3',
            'net_amount'          => 'decimal:3',
            'status'              => SettlementStatus::class,
            'approved_at'         => 'datetime',
            'paid_at'             => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
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

        static::creating(function (self $settlement): void {
            $settlement->creator_id ??= Auth::id();
        });
    }
}
