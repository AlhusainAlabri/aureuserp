<?php

namespace Webkul\Payroll\Models;

use App\Traits\HasApprovalWorkflow;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Database\Factories\LoanFactory;
use Webkul\Payroll\Enums\LoanInstallmentStatus;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\LoanType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\Models\Approval;

class Loan extends Model
{
    use HasApprovalWorkflow, HasChatter, HasFactory, SoftDeletes {
        HasApprovalWorkflow::isApproved as isWorkflowApproved;
    }

    protected $table = 'payroll_loans';

    protected $fillable = [
        'reference_number',
        'employee_id',
        'loan_type',
        'total_amount',
        'installment_count',
        'installment_amount',
        'start_period_year',
        'start_period_month',
        'end_period_year',
        'end_period_month',
        'reason',
        'status',
        'amount_repaid',
        'amount_remaining',
        'approved_at',
        'approved_by',
        'company_id',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'loan_type'           => LoanType::class,
            'status'              => LoanStatus::class,
            'total_amount'        => 'decimal:3',
            'installment_amount'  => 'decimal:3',
            'amount_repaid'       => 'decimal:3',
            'amount_remaining'    => 'decimal:3',
            'approved_at'         => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id')
            ->orderBy('period_year')
            ->orderBy('period_month');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function isFullyApproved(): bool
    {
        return $this->isWorkflowApproved();
    }

    public function generateInstallments(): void
    {
        if ($this->installments()->exists()) {
            return;
        }

        $period = Carbon::create($this->start_period_year, $this->start_period_month, 1);

        for ($i = 0; $i < $this->installment_count; $i++) {
            $this->installments()->create([
                'period_year'  => (int) $period->year,
                'period_month' => (int) $period->month,
                'amount'       => $this->installment_amount,
                'status'       => LoanInstallmentStatus::Scheduled,
            ]);

            $period->addMonth();
        }
    }

    public function activate(): void
    {
        if (! $this->isFullyApproved()) {
            throw new RuntimeException(__('payroll::exceptions.loan_not_approved'));
        }

        $this->update([
            'status'           => LoanStatus::Active,
            'amount_remaining' => round((float) $this->total_amount - (float) $this->amount_repaid, 3),
        ]);

        $this->generateInstallments();
    }

    public function deduct(Payslip $payslip): float
    {
        $installment = $this->installments()
            ->where('period_year', $payslip->period_year)
            ->where('period_month', $payslip->period_month)
            ->where('status', LoanInstallmentStatus::Scheduled)
            ->first();

        if (! $installment) {
            return 0;
        }

        $installment->markDeducted($payslip);

        $deductedAmount = (float) $installment->amount;
        $amountRepaid = round((float) $this->amount_repaid + $deductedAmount, 3);
        $amountRemaining = round(max((float) $this->total_amount - $amountRepaid, 0), 3);

        $this->update([
            'amount_repaid'    => $amountRepaid,
            'amount_remaining' => $amountRemaining,
        ]);

        if ($this->isCompleted()) {
            $this->update(['status' => LoanStatus::Completed]);
        }

        return $deductedAmount;
    }

    public function getProgressPercent(): float
    {
        if ((float) $this->total_amount <= 0) {
            return 0;
        }

        return round(((float) $this->amount_repaid / (float) $this->total_amount) * 100, 2);
    }

    public function isCompleted(): bool
    {
        return (float) $this->amount_remaining <= 0;
    }

    public function onApprovalSubmitted(Approval $approval): void
    {
        $this->updateQuietly(['status' => LoanStatus::PendingApproval]);
    }

    public function onApprovalApproved(Approval $approval): void
    {
        $this->updateQuietly([
            'status'      => LoanStatus::Approved,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);
    }

    public function onApprovalRejected(Approval $approval): void
    {
        $this->updateQuietly(['status' => LoanStatus::Draft]);
    }

    /**
     * @return array{year: int, month: int}
     */
    public static function calculateEndPeriod(int $startYear, int $startMonth, int $installmentCount): array
    {
        $period = Carbon::create($startYear, $startMonth, 1)->addMonths(max($installmentCount - 1, 0));

        return [
            'year'  => (int) $period->year,
            'month' => (int) $period->month,
        ];
    }

    protected static function newFactory(): LoanFactory
    {
        return LoanFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $loan): void {
            $loan->creator_id ??= Auth::id();
            $loan->company_id ??= Auth::user()?->default_company_id;
            $loan->reference_number ??= static::nextReferenceNumber();
            $loan->installment_amount ??= round(
                (float) $loan->total_amount / max((int) $loan->installment_count, 1),
                3,
            );
            $loan->amount_remaining ??= (float) $loan->total_amount;

            $endPeriod = static::calculateEndPeriod(
                (int) $loan->start_period_year,
                (int) $loan->start_period_month,
                (int) $loan->installment_count,
            );

            $loan->end_period_year ??= $endPeriod['year'];
            $loan->end_period_month ??= $endPeriod['month'];
        });
    }

    protected static function nextReferenceNumber(): string
    {
        return DB::transaction(function (): string {
            $year = now()->year;

            $latestNumber = static::query()
                ->where('reference_number', 'like', "LOAN-{$year}-%")
                ->lockForUpdate()
                ->max('reference_number');

            $sequence = $latestNumber ? ((int) substr($latestNumber, -4)) + 1 : 1;

            return sprintf('LOAN-%d-%04d', $year, $sequence);
        });
    }
}
