<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Database\Factories\EmployeeWarningFactory;
use Webkul\Partner\Models\Company;
use Webkul\Security\Models\User;

class EmployeeWarning extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees_employee_warnings';

    protected $fillable = [
        'employee_id',
        'warning_type_id',
        'subject',
        'description',
        'issued_at',
        'effective_date',
        'expiry_date',
        'is_acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'creator_id',
        'company_id',
    ];

    protected $casts = [
        'issued_at'        => 'date',
        'effective_date'   => 'date',
        'expiry_date'      => 'date',
        'is_acknowledged'  => 'boolean',
        'acknowledged_at'  => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function warningType(): BelongsTo
    {
        return $this->belongsTo(WarningType::class, 'warning_type_id');
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date?->isPast() ?? false;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($warning) {
            $warning->creator_id ??= Auth::id();
        });
    }

    protected static function newFactory(): EmployeeWarningFactory
    {
        return EmployeeWarningFactory::new();
    }
}
