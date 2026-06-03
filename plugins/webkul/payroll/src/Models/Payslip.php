<?php

namespace Webkul\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Webkul\Employee\Models\Contract;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Database\Factories\PayslipFactory;
use Webkul\Payroll\Enums\PaymentMethod;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Security\Models\User;

class Payslip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payroll_payslips';

    protected $fillable = [
        'reference_number',
        'batch_id',
        'employee_id',
        'contract_id',
        'period_year',
        'period_month',
        'working_days',
        'worked_days',
        'unpaid_leave_days',
        'basic_salary',
        'gross_amount',
        'deductions_amount',
        'net_amount',
        'employer_cost',
        'payment_method',
        'bank_account_number',
        'bank_name',
        'cheque_number',
        'status',
        'notes',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'status'             => PayslipStatus::class,
            'payment_method'     => PaymentMethod::class,
            'working_days'       => 'decimal:1',
            'worked_days'        => 'decimal:1',
            'unpaid_leave_days'  => 'decimal:1',
            'basic_salary'       => 'decimal:3',
            'gross_amount'       => 'decimal:3',
            'deductions_amount'  => 'decimal:3',
            'net_amount'         => 'decimal:3',
            'employer_cost'      => 'decimal:3',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class, 'batch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function contract(): BelongsTo
    {
        $related = class_exists(Contract::class)
            ? Contract::class
            : Model::class;

        return $this->belongsTo($related, 'contract_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class, 'payslip_id')->orderBy('sort_order');
    }

    public function loanInstallments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class, 'payslip_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function recalculate(): void
    {
        $earningsTotal = $this->getEarningsTotal();
        $deductionsTotal = $this->getDeductionsTotal();
        $employerCostTotal = $this->getEmployerCostTotal();

        $this->update([
            'gross_amount'      => $earningsTotal,
            'deductions_amount' => $deductionsTotal,
            'employer_cost'     => $employerCostTotal,
            'net_amount'        => round($earningsTotal - $deductionsTotal, 3),
        ]);
    }

    public function addLine(SalaryComponent $component, float $amount, ?float $qty = 1): PayslipLine
    {
        return $this->lines()->create([
            'component_id' => $component->id,
            'type'         => $component->type->value,
            'code'         => $component->code,
            'name'         => $component->name,
            'quantity'     => $qty ?? 1,
            'rate'         => $component->default_amount,
            'amount'       => round($amount, 3),
            'sort_order'   => $component->sort_order,
        ]);
    }

    public function removeLine(PayslipLine $line): void
    {
        if ($line->payslip_id !== $this->id) {
            return;
        }

        $line->delete();
        $this->recalculate();
    }

    public function getEarningsTotal(): float
    {
        return (float) $this->lines()
            ->where('type', SalaryComponentType::Earning->value)
            ->sum('amount');
    }

    public function getDeductionsTotal(): float
    {
        return (float) $this->lines()
            ->where('type', SalaryComponentType::Deduction->value)
            ->sum('amount');
    }

    public function getEmployerCostTotal(): float
    {
        return (float) $this->lines()
            ->where('type', SalaryComponentType::EmployerCost->value)
            ->sum('amount');
    }

    public function isDraft(): bool
    {
        return $this->status === PayslipStatus::Draft;
    }

    public function isValidated(): bool
    {
        return in_array($this->status, [PayslipStatus::Validated, PayslipStatus::Paid], true);
    }

    protected static function newFactory(): PayslipFactory
    {
        return PayslipFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $payslip): void {
            $payslip->creator_id ??= Auth::id();
            $payslip->reference_number ??= static::nextReferenceNumber(
                $payslip->period_year,
                $payslip->period_month,
            );
        });
    }

    protected static function nextReferenceNumber(int $year, int $month): string
    {
        return DB::transaction(function () use ($year, $month): string {
            $prefix = sprintf('PSL-%d-%02d-', $year, $month);

            $latestNumber = static::query()
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->where('reference_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->max('reference_number');

            $sequence = $latestNumber ? ((int) substr($latestNumber, -4)) + 1 : 1;

            return sprintf('%s%04d', $prefix, $sequence);
        });
    }
}
