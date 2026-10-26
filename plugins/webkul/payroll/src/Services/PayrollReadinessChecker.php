<?php

namespace Webkul\Payroll\Services;

use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\PayrollBatch;

/**
 * Problems that make a generated batch quietly wrong. Generation still runs; the findings are shown to
 * the person who generated it so they can fix the setup and generate again.
 */
class PayrollReadinessChecker
{
    public function __construct(
        protected BasicSalaryResolver $basicSalary,
        protected AttendanceBridge $attendance,
    ) {}

    /**
     * @return array{no_basic: list<string>, no_pasi: list<string>, open_days: list<string>}
     */
    public function check(PayrollBatch $batch): array
    {
        $year = (int) $batch->period_year;
        $month = (int) $batch->period_month;
        $periodEnd = now()->setDate($year, $month, 1)->endOfMonth();

        $findings = ['no_basic' => [], 'no_pasi' => [], 'open_days' => []];

        $employees = Employee::query()
            ->where('is_active', true)
            ->when($batch->company_id, fn ($query) => $query->where('company_id', $batch->company_id))
            ->get();

        $pasiAssigned = EmployeeComponent::query()
            ->whereIn('employee_id', $employees->modelKeys())
            ->whereHas('component', fn ($query) => $query->whereIn('calculation_type', ['pasi_employee', 'pasi_employer']))
            ->where('start_date', '<=', $periodEnd)
            ->pluck('employee_id')
            ->unique()
            ->all();

        foreach ($employees as $employee) {
            if ($this->basicSalary->forDate($employee, $periodEnd) <= 0) {
                $findings['no_basic'][] = $employee->name;
            }

            if (! in_array($employee->id, $pasiAssigned, true)) {
                $findings['no_pasi'][] = $employee->name;
            }

            if ($this->attendance->openDays($employee, $year, $month) > 0 && ! in_array($employee->name, $findings['open_days'], true)) {
                $findings['open_days'][] = $employee->name;
            }
        }

        return $findings;
    }

    /**
     * Human-readable lines for a notification body.
     *
     * @param  array{no_basic: list<string>, no_pasi: list<string>, open_days: list<string>}  $findings
     * @return list<string>
     */
    public function messages(array $findings): array
    {
        $messages = [];

        foreach (['no_basic', 'no_pasi', 'open_days'] as $key) {
            if ($findings[$key] === []) {
                continue;
            }

            $messages[] = __("payroll::payroll.batch.readiness.{$key}", [
                'count' => count($findings[$key]),
                'names' => implode('، ', array_slice($findings[$key], 0, 5)).(count($findings[$key]) > 5 ? '…' : ''),
            ]);
        }

        return $messages;
    }
}
