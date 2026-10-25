<?php

namespace Webkul\Attendance\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

/**
 * Whose attendance a user may see: everyone for those who can edit days (HR), otherwise their own
 * employee record plus the employees they are the attendance manager of.
 */
class AttendanceScope
{
    /**
     * The Shield permission of the day resource that widens the scope to every employee.
     */
    public const ALL_EMPLOYEES_PERMISSION = 'update_attendance_attendance::day';

    /**
     * Employee ids the user may see; null means all employees.
     *
     * @return Collection<int, int>|null
     */
    public static function employeeIdsFor(User $user): ?Collection
    {
        if ($user->can(self::ALL_EMPLOYEES_PERMISSION)) {
            return null;
        }

        return Employee::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey())
                    ->orWhere('attendance_manager_id', $user->getKey());
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    public static function includes(User $user, int $employeeId): bool
    {
        if ($user->can(self::ALL_EMPLOYEES_PERMISSION)) {
            return true;
        }

        return Employee::query()
            ->whereKey($employeeId)
            ->where(function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey())
                    ->orWhere('attendance_manager_id', $user->getKey());
            })
            ->exists();
    }

    /**
     * Restricts a query to the employees the user may see.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function applyTo(Builder $query, ?User $user, string $column = 'employee_id'): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        $ids = static::employeeIdsFor($user);

        return $ids === null ? $query : $query->whereIn($column, $ids->all());
    }
}
