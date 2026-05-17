<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Employee\Models\EmployeeCategory;

class EmployeeCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, EmployeeCategory $employeeCategory): bool
    {
        return $authUser->can('view_any_employee_employee::category');
    }

    public function view(AuthUser $authUser, EmployeeCategory $employeeCategory): bool
    {
        return $authUser->can('view_employee_employee::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_employee_employee::category');
    }

    public function update(AuthUser $authUser, EmployeeCategory $employeeCategory): bool
    {
        return $authUser->can('update_employee_employee::category');
    }

    public function delete(AuthUser $authUser, EmployeeCategory $employeeCategory): bool
    {
        return $authUser->can('delete_employee_employee::category');
    }

    public function deleteAny(AuthUser $authUser, EmployeeCategory $employeeCategory): bool
    {
        return $authUser->can('delete_any_employee_employee::category');
    }
}
