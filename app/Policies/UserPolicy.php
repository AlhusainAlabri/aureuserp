<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_security_user');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view_security_user');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_security_user');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('update_security_user');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete_security_user');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_security_user');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('restore_security_user');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_security_user');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_security_user');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_security_user');
    }
}
