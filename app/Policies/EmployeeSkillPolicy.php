<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Employee\Models\EmployeeSkill;

class EmployeeSkillPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('view_any_employee_employee::skill');
    }

    public function view(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('view_employee_employee::skill');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_employee_employee::skill');
    }

    public function update(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('update_employee_employee::skill');
    }

    public function delete(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('delete_employee_employee::skill');
    }

    public function deleteAny(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('delete_any_employee_employee::skill');
    }

    public function restore(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('restore_employee_employee::skill');
    }

    public function restoreAny(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('restore_any_employee_employee::skill');
    }

    public function forceDelete(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('force_delete_employee_employee::skill');
    }

    public function forceDeleteAny(AuthUser $authUser, EmployeeSkill $employeeSkill): bool
    {
        return $authUser->can('force_delete_any_employee_employee::skill');
    }
}
