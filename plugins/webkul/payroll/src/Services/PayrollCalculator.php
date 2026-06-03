<?php

namespace Webkul\Payroll\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\BatchStatus;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\LoanInstallmentStatus;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PaymentMethod;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\TimeOff\Models\Leave;

class PayrollCalculator
{
    private const float WORKING_DAYS = 22.0;

    public function calculatePayslip(Employee $employee, int $year, int $month): Payslip
    {
        $batch = $this->resolveBatch((int) $employee->company_id, $year, $month);

        return $this->buildPayslip($employee, $batch, $year, $month);
    }

    public function recalculatePayslip(Payslip $payslip): void
    {
        if (! $payslip->isDraft()) {
            return;
        }

        $payslip->loadMissing(['employee', 'batch']);

        $this->buildPayslip(
            $payslip->employee,
            $payslip->batch,
            $payslip->period_year,
            $payslip->period_month,
            $payslip,
        );
    }

    public function generateBatch(int $year, int $month, ?int $companyId = null): PayrollBatch
    {
        $companyId ??= Auth::user()?->default_company_id;

        $batch = PayrollBatch::query()->firstOrCreate(
            [
                'company_id'   => $companyId,
                'period_year'  => $year,
                'period_month' => $month,
            ],
            [
                'pay_date' => Carbon::create($year, $month, min(25, Carbon::create($year, $month)->daysInMonth)),
                'status'   => BatchStatus::Draft,
            ],
        );

        return $this->generateForBatch($batch);
    }

    public function generateForBatch(PayrollBatch $batch): PayrollBatch
    {
        return DB::transaction(function () use ($batch): PayrollBatch {
            $employees = Employee::query()
                ->where('is_active', true)
                ->when($batch->company_id, fn ($query) => $query->where('company_id', $batch->company_id))
                ->get();

            foreach ($employees as $employee) {
                $this->buildPayslip($employee, $batch, $batch->period_year, $batch->period_month);
            }

            $batch->recalculateTotals();

            return $batch->fresh(['payslips']);
        });
    }

    protected function buildPayslip(
        Employee $employee,
        PayrollBatch $batch,
        int $year,
        int $month,
        ?Payslip $existing = null,
    ): Payslip {
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $assignments = EmployeeComponent::query()
            ->with('component')
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $periodEnd)
            ->where(function ($query) use ($periodStart): void {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $periodStart);
            })
            ->whereHas('component', fn ($query) => $query->active())
            ->get()
            ->sortBy(fn (EmployeeComponent $assignment): int => $assignment->component?->sort_order ?? 0);

        $contractualBasic = $this->resolveContractualBasic($assignments);
        $unpaidLeaveDays = $this->resolveUnpaidLeaveDays($employee, $year, $month, $periodStart, $periodEnd);
        $workedDays = max(self::WORKING_DAYS - $unpaidLeaveDays, 0);
        $basicSalary = $this->prorateBasic($contractualBasic, $workedDays);

        $employee->loadMissing('bankAccount.bank');

        $payslip = $existing ?? Payslip::query()->firstOrNew([
            'batch_id'    => $batch->id,
            'employee_id' => $employee->id,
        ]);

        $payslip->fill([
            'period_year'         => $year,
            'period_month'        => $month,
            'working_days'        => self::WORKING_DAYS,
            'worked_days'         => $workedDays,
            'unpaid_leave_days'   => $unpaidLeaveDays,
            'basic_salary'        => $basicSalary,
            'payment_method'      => PaymentMethod::BankTransfer,
            'bank_account_number' => $employee->bankAccount?->account_number,
            'bank_name'           => $employee->bankAccount?->bank?->name,
            'status'              => PayslipStatus::Draft,
        ]);

        $payslip->save();

        $payslip->lines()->delete();

        $payslip->gross_amount = 0;

        $firstPass = $assignments->filter(
            fn (EmployeeComponent $assignment): bool => $assignment->component?->calculation_type !== CalculationType::PercentOfGross,
        );

        $secondPass = $assignments->filter(
            fn (EmployeeComponent $assignment): bool => $assignment->component?->calculation_type === CalculationType::PercentOfGross,
        );

        foreach ($firstPass->merge($secondPass) as $assignment) {
            $component = $assignment->component;

            if ($component === null) {
                continue;
            }

            $amount = $this->resolveComponentAmount($component, $assignment, $payslip, $basicSalary);

            if ($amount == 0.0) {
                continue;
            }

            $payslip->addLine($component, $amount);

            if ($component->type === SalaryComponentType::Earning) {
                $payslip->gross_amount = $payslip->getEarningsTotal();
            }
        }

        $this->appendLoanDeductions($payslip, $employee, $year, $month);

        $payslip->recalculate();

        return $payslip->fresh(['lines']);
    }

    protected function resolveBatch(?int $companyId, int $year, int $month): PayrollBatch
    {
        $companyId ??= Auth::user()?->default_company_id;

        return PayrollBatch::query()->firstOrCreate(
            [
                'company_id'   => $companyId,
                'period_year'  => $year,
                'period_month' => $month,
            ],
            [
                'pay_date' => Carbon::create($year, $month, min(25, Carbon::create($year, $month)->daysInMonth)),
                'status'   => BatchStatus::Draft,
            ],
        );
    }

    protected function resolveContractualBasic($assignments): float
    {
        foreach ($assignments as $assignment) {
            if ($assignment->component?->code === 'BASIC') {
                if ($assignment->amount !== null) {
                    return (float) $assignment->amount;
                }

                return (float) ($assignment->component->default_amount ?? 0);
            }
        }

        return 0.0;
    }

    protected function prorateBasic(float $contractualBasic, float $workedDays): float
    {
        if ($workedDays >= self::WORKING_DAYS) {
            return round($contractualBasic, 3);
        }

        return round(
            $contractualBasic * ($workedDays / self::WORKING_DAYS),
            3,
        );
    }

    protected function resolveUnpaidLeaveDays(
        Employee $employee,
        int $year,
        int $month,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): float {
        if (! Schema::hasTable('time_off_leaves')) {
            return 0.0;
        }

        if (! class_exists(Leave::class)) {
            return 0.0;
        }

        $query = Leave::query()
            ->where('employee_id', $employee->id)
            ->whereIn('state', ['validate_one', 'validate_two'])
            ->where('date_from', '<=', $periodEnd)
            ->where('date_to', '>=', $periodStart);

        if (Schema::hasTable('time_off_leave_types')) {
            $query->whereHas('holidayStatus', fn ($leaveTypeQuery) => $leaveTypeQuery->where('unpaid', true));
        }

        return (float) $query->sum('number_of_days');
    }

    protected function resolveComponentAmount(
        SalaryComponent $component,
        EmployeeComponent $assignment,
        Payslip $payslip,
        float $basicSalary,
    ): float {
        if ($component->code === 'BASIC' && $component->type === SalaryComponentType::Earning) {
            return $basicSalary;
        }

        $amountOverride = $assignment->amount !== null
            && in_array($component->calculation_type, [CalculationType::Fixed, CalculationType::HoursBased], true)
            ? (float) $assignment->amount
            : null;

        $percentOverride = $assignment->percent !== null
            && in_array($component->calculation_type, [CalculationType::PercentOfBasic, CalculationType::PercentOfGross], true)
            ? (float) $assignment->percent
            : null;

        if ($component->calculation_type === CalculationType::PercentOfBasic) {
            $percent = $percentOverride ?? (float) ($component->default_percent ?? 0);

            return round($basicSalary * $percent / 100, 3);
        }

        if ($component->calculation_type === CalculationType::Fixed && $amountOverride !== null) {
            return round($amountOverride, 3);
        }

        if ($component->calculation_type === CalculationType::HoursBased && $amountOverride !== null) {
            return round(
                $amountOverride * (float) $payslip->worked_days / max((float) $payslip->working_days, 1),
                3,
            );
        }

        if ($component->calculation_type === CalculationType::PercentOfGross && $percentOverride !== null) {
            return round((float) $payslip->gross_amount * $percentOverride / 100, 3);
        }

        return $component->calculateAmount($payslip);
    }

    protected function appendLoanDeductions(Payslip $payslip, Employee $employee, int $year, int $month): void
    {
        if (! class_exists(Loan::class)) {
            return;
        }

        $loans = Loan::query()
            ->where('employee_id', $employee->id)
            ->where('status', LoanStatus::Active)
            ->with(['installments' => fn ($query) => $query
                ->where('period_year', $year)
                ->where('period_month', $month),
            ])
            ->get();

        foreach ($loans as $loan) {
            $installment = $loan->installments->first();

            if ($installment === null || $installment->status !== LoanInstallmentStatus::Scheduled) {
                continue;
            }

            $payslip->lines()->create([
                'component_id' => null,
                'type'         => SalaryComponentType::Deduction->value,
                'code'         => 'LOAN',
                'name'         => __('payroll::payslip.loan_repayment', ['reference' => $loan->reference_number]),
                'quantity'     => 1,
                'rate'         => $installment->amount,
                'amount'       => round((float) $installment->amount, 3),
                'sort_order'   => 900,
            ]);
        }
    }
}
