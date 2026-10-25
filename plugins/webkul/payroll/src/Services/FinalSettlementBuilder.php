<?php

namespace Webkul\Payroll\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Settings\GratuitySettings;

class FinalSettlementBuilder
{
    public function __construct(
        protected ServicePeriodResolver $periods,
        protected GratuityCalculator $gratuity,
        protected BasicSalaryResolver $basics,
        protected LeaveBalanceResolver $leave,
    ) {}

    /**
     * @throws RuntimeException when the employee cannot be settled yet
     */
    public function build(Employee $employee): EndOfServiceSettlement
    {
        if ($employee->departure_date === null) {
            throw new RuntimeException(__('payroll::exceptions.settlement_no_departure_date'));
        }

        $period = $this->periods->forEmployee($employee);

        if ($period === null) {
            throw new RuntimeException(__('payroll::exceptions.settlement_no_joining_date'));
        }

        return DB::transaction(function () use ($employee, $period): EndOfServiceSettlement {
            $live = EndOfServiceSettlement::query()
                ->where('employee_id', $employee->id)
                ->where('status', '!=', SettlementStatus::Cancelled->value)
                ->lockForUpdate()
                ->exists();

            if ($live) {
                throw new RuntimeException(__('payroll::exceptions.settlement_exists'));
            }

            $end = $period['end'];
            $divisor = max(app(GratuitySettings::class)->leave_payout_divisor, 1);
            $result = $this->gratuity->calculate($employee, $period['start'], $end);
            $lastBasic = $this->basics->forDate($employee, $end);
            $unusedDays = $this->leave->unusedDays($employee, $end);

            return EndOfServiceSettlement::query()->create([
                'employee_id'         => $employee->id,
                'company_id'          => $employee->company_id,
                'join_date'           => $period['start']->toDateString(),
                'departure_date'      => $end->toDateString(),
                'service_days'        => $result->serviceDays,
                'last_basic'          => $lastBasic,
                'gratuity_breakdown'  => [
                    'reason'        => $result->ineligibleReason,
                    'service_years' => round($result->serviceYears, 4),
                    'rows'          => $result->breakdown,
                ],
                'gratuity_amount'     => $result->amount,
                'unused_leave_days'   => $unusedDays,
                'leave_payout_amount' => round($unusedDays * $lastBasic / $divisor, 3),
                'loan_offset_amount'  => $this->loanOffset($employee),
                'final_salary_amount' => $this->finalSalary($employee, $lastBasic, $end),
                'other_adjustment'    => 0,
                'status'              => SettlementStatus::Draft,
                'creator_id'          => Auth::id(),
            ]);
        });
    }

    protected function loanOffset(Employee $employee): float
    {
        return round((float) Loan::query()
            ->where('employee_id', $employee->id)
            ->where('status', LoanStatus::Active->value)
            ->sum('amount_remaining'), 3);
    }

    /**
     * Basic salary pro-rated by calendar day for the month of departure, unless that month was already paid.
     */
    protected function finalSalary(Employee $employee, float $lastBasic, CarbonInterface $departure): float
    {
        $alreadyPaid = Payslip::query()
            ->where('employee_id', $employee->id)
            ->where('period_year', $departure->year)
            ->where('period_month', $departure->month)
            ->whereIn('status', [PayslipStatus::Validated->value, PayslipStatus::Paid->value])
            ->exists();

        if ($alreadyPaid) {
            return 0.0;
        }

        return round($lastBasic * $departure->day / $departure->daysInMonth, 3);
    }
}
