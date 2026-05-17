<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PartnerPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_sale_customer');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view_sale_customer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_customer');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('update_sale_customer');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete_sale_customer');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_sale_customer');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('restore_sale_customer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_sale_customer');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_sale_customer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_sale_customer');
    }
}
