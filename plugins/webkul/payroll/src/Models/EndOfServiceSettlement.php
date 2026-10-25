<?php

namespace Webkul\Payroll\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\LoanInstallmentStatus;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Payroll\Settings\GratuitySettings;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\Models\Approval;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

class EndOfServiceSettlement extends Model
{
    use HasApprovalWorkflow, SoftDeletes {
        HasApprovalWorkflow::isApproved as isWorkflowApproved;
    }

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

    /**
     * Only a draft can be sent to approval; a paid or cancelled settlement must never re-enter the workflow.
     */
    public function canSubmitForApproval(?int $userId = null): bool
    {
        return $this->status === SettlementStatus::Draft;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [SettlementStatus::Paid, SettlementStatus::Cancelled], true);
    }

    /**
     * @return Builder<Loan>
     */
    public static function activeLoansOf(int $employeeId): Builder
    {
        return Loan::query()
            ->where('employee_id', $employeeId)
            ->where('status', LoanStatus::Active->value);
    }

    public function isEditable(): bool
    {
        return $this->status === SettlementStatus::Draft;
    }

    public function canBePaid(): bool
    {
        if (in_array($this->status, [SettlementStatus::Paid, SettlementStatus::Cancelled], true)) {
            return false;
        }

        return ! $this->hasActiveApprovalFlow() || $this->isWorkflowApproved();
    }

    public function markAsPaid(): void
    {
        if (! $this->canBePaid()) {
            throw new RuntimeException(__('payroll::exceptions.settlement_cannot_be_paid'));
        }

        DB::transaction(function (): void {
            $current = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! $current->canBePaid()) {
                throw new RuntimeException(__('payroll::exceptions.settlement_cannot_be_paid'));
            }

            $current->assertBuiltFromCurrentData();

            $current->update([
                'status'  => SettlementStatus::Paid,
                'paid_at' => now(),
            ]);

            $current->settleLoans();
        });

        $this->refresh();
    }

    public function cancel(): void
    {
        if ($this->isClosed()) {
            throw new RuntimeException(__('payroll::exceptions.settlement_cannot_be_cancelled'));
        }

        DB::transaction(function (): void {
            $approval = $this->currentApproval();

            if ($approval !== null) {
                app(ApprovalEngine::class)->cancel($approval);
            }

            $this->update(['status' => SettlementStatus::Cancelled]);
        });
    }

    public function recalculateNet(): void
    {
        $this->net_amount = round(
            (float) $this->gratuity_amount
            + (float) $this->leave_payout_amount
            + (float) $this->final_salary_amount
            + (float) $this->other_adjustment
            - (float) $this->loan_offset_amount,
            3,
        );
    }

    public function onApprovalSubmitted(Approval $approval): void
    {
        if ($this->isClosed()) {
            return;
        }

        $this->updateQuietly(['status' => SettlementStatus::PendingApproval]);
    }

    public function onApprovalApproved(Approval $approval): void
    {
        if ($this->isClosed()) {
            return;
        }

        $this->updateQuietly([
            'status'      => SettlementStatus::Approved,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);
    }

    public function onApprovalRejected(Approval $approval): void
    {
        if ($this->isClosed()) {
            return;
        }

        $this->updateQuietly(['status' => SettlementStatus::Draft]);
    }

    /**
     * The offset and the final salary were taken when the draft was built; refuse to pay if the loans or the
     * departure-month payslip changed since, so nothing is deducted or paid twice.
     *
     * @throws RuntimeException
     */
    protected function assertBuiltFromCurrentData(): void
    {
        $storedIds = $this->gratuity_breakdown['loan_ids'] ?? null;

        if ($storedIds !== null) {
            $active = static::activeLoansOf($this->employee_id)->get(['id', 'amount_remaining']);

            $sameLoans = $active->pluck('id')->sort()->values()->all() === collect($storedIds)->sort()->values()->all();
            $sameBalance = round((float) $active->sum('amount_remaining'), 3) === round((float) $this->loan_offset_amount, 3);

            if (! $sameLoans || ! $sameBalance) {
                throw new RuntimeException(__('payroll::exceptions.settlement_changed'));
            }
        }

        if ((float) $this->final_salary_amount > 0 && Payslip::query()
            ->where('employee_id', $this->employee_id)
            ->where('period_year', $this->departure_date->year)
            ->where('period_month', $this->departure_date->month)
            ->whereIn('status', [PayslipStatus::Validated->value, PayslipStatus::Paid->value])
            ->exists()) {
            throw new RuntimeException(__('payroll::exceptions.settlement_changed'));
        }
    }

    /**
     * Paying the settlement clears the loans that were offset: the offset has been taken from what is owed.
     */
    protected function settleLoans(): void
    {
        $offsetIds = $this->gratuity_breakdown['loan_ids'] ?? null;

        static::activeLoansOf($this->employee_id)
            ->when($offsetIds !== null, fn (Builder $query) => $query->whereKey($offsetIds))
            ->each(function (Loan $loan): void {
                $loan->installments()
                    ->where('status', LoanInstallmentStatus::Scheduled->value)
                    ->update(['status' => LoanInstallmentStatus::Cancelled->value]);

                $loan->update([
                    'amount_repaid'    => $loan->total_amount,
                    'amount_remaining' => 0,
                    'status'           => LoanStatus::Completed,
                ]);
            });
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $settlement): void {
            $settlement->creator_id ??= Auth::id();
        });

        static::saving(function (self $settlement): void {
            if ($settlement->exists && $settlement->getOriginal('status') === SettlementStatus::Paid
                && ! $settlement->isDirty('deleted_at')) {
                throw new RuntimeException(__('payroll::exceptions.settlement_locked'));
            }

            if ($settlement->exists && $settlement->isDirty('unused_leave_days')) {
                $divisor = max(app(GratuitySettings::class)->leave_payout_divisor, 1);

                $settlement->leave_payout_amount = round((float) $settlement->unused_leave_days * (float) $settlement->last_basic / $divisor, 3);
            }

            $settlement->live_employee_id = $settlement->status === SettlementStatus::Cancelled ? null : $settlement->employee_id;

            $settlement->recalculateNet();
        });
    }
}
