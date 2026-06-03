<?php

namespace Webkul\Payroll\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Models\Account;
use Webkul\Payroll\Concerns\HasLocalizedDisplayName;
use Webkul\Payroll\Database\Factories\SalaryComponentFactory;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class SalaryComponent extends Model
{
    use HasFactory;
    use HasLocalizedDisplayName;

    protected $table = 'payroll_salary_components';

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'type',
        'calculation_type',
        'default_amount',
        'default_percent',
        'formula',
        'is_taxable',
        'is_active',
        'sort_order',
        'account_id',
        'company_id',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'type'              => SalaryComponentType::class,
            'calculation_type'  => CalculationType::class,
            'is_taxable'        => 'boolean',
            'is_active'         => 'boolean',
            'default_amount'    => 'decimal:3',
            'default_percent'   => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        $related = class_exists(Account::class)
            ? Account::class
            : Model::class;

        return $this->belongsTo($related, 'account_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeComponent::class, 'component_id');
    }

    public function payslipLines(): HasMany
    {
        return $this->hasMany(PayslipLine::class, 'component_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeEarnings(Builder $query): Builder
    {
        return $query->where('type', SalaryComponentType::Earning);
    }

    public function scopeDeductions(Builder $query): Builder
    {
        return $query->where('type', SalaryComponentType::Deduction);
    }

    /**
     * @return array<int, string>
     */
    public static function localizedSelectOptions(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (self $component): array => [$component->id => $component->display_name])
            ->all();
    }

    public function calculateAmount(Payslip $payslip): float
    {
        return match ($this->calculation_type) {
            CalculationType::Fixed          => (float) ($this->default_amount ?? 0),
            CalculationType::PercentOfBasic => round(
                (float) $payslip->basic_salary * (float) ($this->default_percent ?? 0) / 100,
                3,
            ),
            CalculationType::PercentOfGross => round(
                (float) $payslip->gross_amount * (float) ($this->default_percent ?? 0) / 100,
                3,
            ),
            CalculationType::HoursBased => round(
                (float) ($this->default_amount ?? 0)
                * (float) $payslip->worked_days
                / max((float) $payslip->working_days, 1),
                3,
            ),
            CalculationType::Formula => 0,
        };
    }

    protected static function newFactory(): SalaryComponentFactory
    {
        return SalaryComponentFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $component): void {
            $component->creator_id ??= Auth::id();
            $component->company_id ??= Auth::user()?->default_company_id;
        });
    }
}
