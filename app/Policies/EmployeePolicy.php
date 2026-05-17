<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Employee\Models\Employee;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('view_any_employee_employee');
    }

    public function view(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('view_employee_employee');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_employee_employee');
    }

    public function update(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('update_employee_employee');
    }

    public function delete(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('delete_employee_employee');
    }

    public function deleteAny(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('delete_any_employee_employee');
    }

    public function restore(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('restore_employee_employee');
    }

    public function restoreAny(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('restore_any_employee_employee');
    }

    public function forceDelete(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('force_delete_employee_employee');
    }

    public function forceDeleteAny(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can('force_delete_any_employee_employee');
    }
}
