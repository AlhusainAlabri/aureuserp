<?php

namespace Webkul\Correspondence\Services;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Models\Department;
use Webkul\Security\Models\User;

class CorrespondenceVisibilityService
{
    /**
     * @return array<int, int>
     */
    public static function correspondenceDepartmentIdsForUser(?User $user): array
    {
        $employeesDepartmentId = $user?->employee?->department_id;

        if (! $employeesDepartmentId) {
            return [];
        }

        return Department::query()
            ->where('employees_department_id', $employeesDepartmentId)
            ->pluck('id')
            ->all();
    }

    public static function applyDepartmentScope(Builder $query, ?User $user): Builder
    {
        $departmentIds = self::correspondenceDepartmentIdsForUser($user);

        return $query->where(function (Builder $query) use ($user, $departmentIds): void {
            $query->where('creator_id', $user?->id)
                ->orWhere('to_user_id', $user?->id);

            if ($departmentIds !== []) {
                $query->orWhereIn('from_department_id', $departmentIds)
                    ->orWhereIn('to_department_id', $departmentIds);
            }
        });
    }
}
