<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AddressPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_partner_address');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view_partner_address');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_partner_address');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('update_partner_address');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete_partner_address');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_partner_address');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('restore_partner_address');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_partner_address');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_partner_address');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_partner_address');
    }
}
