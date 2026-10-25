<?php

namespace Webkul\Payroll\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;

class LeaveBalanceResolver
{
    /**
     * Unused balance of the paid leave types that require an allocation: allocations approved on or
     * before $asOf minus approved leaves, never below zero. Zero when Time Off is not installed.
     */
    public function unusedDays(Employee $employee, CarbonInterface $asOf): float
    {
        foreach (['time_off_leave_types', 'time_off_leave_allocations', 'time_off_leaves'] as $table) {
            if (! Schema::hasTable($table)) {
                return 0.0;
            }
        }

        $typeIds = DB::table('time_off_leave_types')
            ->where('requires_allocation', 'yes')
            ->where(function ($query): void {
                $query->whereNull('unpaid')->orWhere('unpaid', false);
            })
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($typeIds->isEmpty()) {
            return 0.0;
        }

        $allocated = (float) DB::table('time_off_leave_allocations')
            ->where('employee_id', $employee->id)
            ->whereIn('holiday_status_id', $typeIds)
            ->where('state', 'validate_two')
            ->whereDate('date_from', '<=', $asOf->toDateString())
            ->sum('number_of_days');

        $taken = (float) DB::table('time_off_leaves')
            ->where('employee_id', $employee->id)
            ->whereIn('holiday_status_id', $typeIds)
            ->whereIn('state', ['validate_one', 'validate_two'])
            ->sum('number_of_days');

        return round(max($allocated - $taken, 0.0), 1);
    }
}
