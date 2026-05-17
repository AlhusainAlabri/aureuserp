<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Employee\Models\WorkLocation;

class WorkLocationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('view_any_employee_work::location');
    }

    public function view(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('view_employee_work::location');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_employee_work::location');
    }

    public function update(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('update_employee_work::location');
    }

    public function delete(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('delete_employee_work::location');
    }

    public function deleteAny(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('delete_any_employee_work::location');
    }

    public function restore(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('restore_employee_work::location');
    }

    public function restoreAny(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('restore_any_employee_work::location');
    }

    public function forceDelete(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('force_delete_employee_work::location');
    }

    public function forceDeleteAny(AuthUser $authUser, WorkLocation $workLocation): bool
    {
        return $authUser->can('force_delete_any_employee_work::location');
    }
}
