<?php

namespace App\Observers;

use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;

class EmployeeDepartmentObserver
{
    public function saved(Employee $employee): void
    {
        if (! Schema::hasTable('department_employee')) {
            return;
        }

        $pivotRows = $employee->departments()->get();

        if ($pivotRows->isEmpty() && $employee->department_id) {
            $employee->departments()->sync([
                $employee->department_id => [
                    'is_primary' => true,
                    'start_date' => now()->toDateString(),
                ],
            ]);

            return;
        }

        if ($pivotRows->isEmpty()) {
            return;
        }

        $primary = $pivotRows->firstWhere('pivot.is_primary', true);

        if (! $primary) {
            $latest = $employee->departments()->orderByDesc('department_employee.id')->first();

            if ($latest) {
                $employee->departments()->updateExistingPivot($latest->id, ['is_primary' => true]);
                $employee->updateQuietly(['department_id' => $latest->id]);
            }

            return;
        }

        if ((int) $employee->department_id !== (int) $primary->id) {
            $employee->updateQuietly(['department_id' => $primary->id]);
        }

        $employee->departments()
            ->wherePivot('is_primary', true)
            ->where('employees_departments.id', '!=', $primary->id)
            ->get()
            ->each(fn ($department) => $employee->departments()->updateExistingPivot($department->id, ['is_primary' => false]));
    }
}
