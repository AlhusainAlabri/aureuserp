<?php

namespace Webkul\Payroll\Services;

use App\Models\Hr\EmployeeContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;

class ServicePeriodResolver
{
    /**
     * Start = earliest first joining date over the employee's contracts, else the earliest contract
     * start date; end = departure date.
     *
     * @return array{start: Carbon, end: Carbon}|null null when either date cannot be determined
     */
    public function forEmployee(Employee $employee): ?array
    {
        if ($employee->departure_date === null || ! Schema::hasTable('employee_contracts')) {
            return null;
        }

        $contracts = EmployeeContract::query()->where('employee_id', $employee->id);

        $start = (clone $contracts)->whereNotNull('first_joining_date')->min('first_joining_date')
            ?? (clone $contracts)->min('start_date');

        if ($start === null) {
            return null;
        }

        return [
            'start' => Carbon::parse($start)->startOfDay(),
            'end'   => Carbon::parse($employee->departure_date)->startOfDay(),
        ];
    }
}
