<?php

namespace App\Services\Hr;

use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;

class EmployeeDepartmentService
{
    public function syncDepartments(Employee $employee, array $departmentIds, int|string|null $primaryDepartmentId = null): void
    {
        if (! Schema::hasTable('department_employee')) {
            return;
        }

        $departmentIds = array_values(array_unique(array_map(
            intval(...),
            array_filter($departmentIds, fn (mixed $id): bool => filled($id)),
        )));

        $primaryDepartmentId = filled($primaryDepartmentId) ? (int) $primaryDepartmentId : null;

        if ($departmentIds === []) {
            return;
        }

        $primaryDepartmentId ??= $departmentIds[0];

        if (! in_array($primaryDepartmentId, $departmentIds, true)) {
            $primaryDepartmentId = $departmentIds[0];
        }

        $syncData = [];

        foreach ($departmentIds as $departmentId) {
            $syncData[$departmentId] = [
                'is_primary' => (int) $departmentId === (int) $primaryDepartmentId,
                'start_date' => now()->toDateString(),
            ];
        }

        $employee->departments()->sync($syncData);
        $employee->updateQuietly(['department_id' => $primaryDepartmentId]);
    }
}
