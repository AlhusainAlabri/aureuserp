<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Employee\Models\EmployeeJobPosition;

class EmployeeJobPositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('view_any_employee_job::position');
    }

    public function view(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('view_employee_job::position');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_employee_job::position');
    }

    public function update(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('update_employee_job::position');
    }

    public function delete(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('delete_employee_job::position');
    }

    public function deleteAny(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('delete_any_employee_job::position');
    }

    public function restore(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('restore_employee_job::position');
    }

    public function restoreAny(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('restore_any_employee_job::position');
    }

    public function forceDelete(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('force_delete_employee_job::position');
    }

    public function forceDeleteAny(AuthUser $authUser, EmployeeJobPosition $employeeJobPosition): bool
    {
        return $authUser->can('force_delete_any_employee_job::position');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_employee_job::position');
    }
}
