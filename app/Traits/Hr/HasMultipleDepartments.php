<?php

namespace App\Traits\Hr;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Employee\Models\Department;

trait HasMultipleDepartments
{
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_employee', 'employee_id', 'department_id')
            ->withPivot(['is_primary', 'start_date', 'end_date'])
            ->withTimestamps();
    }

    public function primaryDepartments(): BelongsToMany
    {
        return $this->departments()->wherePivot('is_primary', true);
    }

    public function syncDepartments(array $departmentIds, ?int $primaryDepartmentId = null): void
    {
        $departmentIds = array_values(array_unique(array_filter($departmentIds)));

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

        $this->departments()->sync($syncData);
        $this->updateQuietly(['department_id' => $primaryDepartmentId]);
    }
}
