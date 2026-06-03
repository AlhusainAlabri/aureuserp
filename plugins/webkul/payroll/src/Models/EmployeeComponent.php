<?php

namespace Webkul\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Database\Factories\EmployeeComponentFactory;
use Webkul\Security\Models\User;

class EmployeeComponent extends Model
{
    use HasFactory;

    protected $table = 'payroll_employee_components';

    protected $fillable = [
        'employee_id',
        'component_id',
        'amount',
        'percent',
        'start_date',
        'end_date',
        'notes',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:3',
            'percent'    => 'decimal:2',
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    protected static function newFactory(): EmployeeComponentFactory
    {
        return EmployeeComponentFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $employeeComponent): void {
            $employeeComponent->creator_id ??= Auth::id();
        });
    }
}
