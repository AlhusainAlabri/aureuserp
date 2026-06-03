<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Employee\Models\WarningType;

class WarningTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('view_any_employee_warning::type');
    }

    public function view(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('view_employee_warning::type');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_employee_warning::type');
    }

    public function update(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('update_employee_warning::type');
    }

    public function delete(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('delete_employee_warning::type');
    }

    public function restore(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('restore_employee_warning::type');
    }

    public function deleteAny(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('delete_any_employee_warning::type');
    }

    public function forceDelete(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('force_delete_employee_warning::type');
    }

    public function forceDeleteAny(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('force_delete_any_employee_warning::type');
    }

    public function restoreAny(AuthUser $authUser, WarningType $warningType): bool
    {
        return $authUser->can('restore_any_employee_warning::type');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_employee_warning::type');
    }
}
