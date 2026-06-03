<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Payroll\Models\EmployeeComponent;

class EmployeeComponentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('view_any_payroll_employee::component');
    }

    public function view(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('view_payroll_employee::component');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_payroll_employee::component');
    }

    public function update(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('update_payroll_employee::component');
    }

    public function delete(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('delete_payroll_employee::component');
    }

    public function restore(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('restore_payroll_employee::component');
    }

    public function deleteAny(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('delete_any_payroll_employee::component');
    }

    public function forceDelete(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('force_delete_payroll_employee::component');
    }

    public function forceDeleteAny(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('force_delete_any_payroll_employee::component');
    }

    public function restoreAny(AuthUser $authUser, EmployeeComponent $employeeComponent): bool
    {
        return $authUser->can('restore_any_payroll_employee::component');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_payroll_employee::component');
    }
}
