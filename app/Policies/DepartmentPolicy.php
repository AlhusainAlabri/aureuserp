<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\Department;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('view_any_recruitment_department');
    }

    public function view(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('view_recruitment_department');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_department');
    }

    public function update(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('update_recruitment_department');
    }

    public function delete(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('delete_recruitment_department');
    }

    public function deleteAny(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('delete_any_recruitment_department');
    }

    public function restore(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('restore_recruitment_department');
    }

    public function restoreAny(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('restore_any_recruitment_department');
    }

    public function forceDelete(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('force_delete_recruitment_department');
    }

    public function forceDeleteAny(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('force_delete_any_recruitment_department');
    }
}
