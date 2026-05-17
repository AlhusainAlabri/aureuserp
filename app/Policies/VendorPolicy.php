<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class VendorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_invoice_vendor');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view_invoice_vendor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_vendor');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('update_invoice_vendor');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete_invoice_vendor');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_invoice_vendor');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('restore_invoice_vendor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_invoice_vendor');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_invoice_vendor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_invoice_vendor');
    }
}
