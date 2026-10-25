<?php

namespace Webkul\Payroll\Services;

use Carbon\CarbonInterface;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\EmployeeComponent;

class BasicSalaryResolver
{
    /**
     * The BASIC component assignment in force on the date: its amount, else the component default, else 0.
     */
    public function forDate(Employee $employee, CarbonInterface $date): float
    {
        $assignment = EmployeeComponent::query()
            ->with('component')
            ->where('employee_id', $employee->id)
            ->whereHas('component', fn ($query) => $query->where('code', 'BASIC'))
            ->whereDate('start_date', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date->toDateString());
            })
            ->orderByDesc('start_date')
            ->first();

        if ($assignment === null) {
            return 0.0;
        }

        return (float) ($assignment->amount ?? $assignment->component?->default_amount ?? 0);
    }
}
