<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Employee\Models\EmployeeSubmission;

class EmployeeSubmissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('view_any_employee_submission');
    }

    public function view(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('view_employee_submission');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_employee_submission');
    }

    public function update(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('update_employee_submission');
    }

    public function delete(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('delete_employee_submission');
    }

    public function restore(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('restore_employee_submission');
    }

    public function deleteAny(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('delete_any_employee_submission');
    }

    public function forceDelete(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('force_delete_employee_submission');
    }

    public function forceDeleteAny(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('force_delete_any_employee_submission');
    }

    public function restoreAny(AuthUser $authUser, EmployeeSubmission $employeeSubmission): bool
    {
        return $authUser->can('restore_any_employee_submission');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_employee_submission');
    }
}
