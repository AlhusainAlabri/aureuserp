<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Purchase\Models\Requisition;

class RequisitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('view_any_purchase_purchase::agreement');
    }

    public function view(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('view_purchase_purchase::agreement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_purchase_purchase::agreement');
    }

    public function update(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('update_purchase_purchase::agreement');
    }

    public function delete(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('delete_purchase_purchase::agreement');
    }

    public function deleteAny(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('delete_any_purchase_purchase::agreement');
    }

    public function restore(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('restore_purchase_purchase::agreement');
    }

    public function restoreAny(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('restore_any_purchase_purchase::agreement');
    }

    public function forceDelete(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('force_delete_purchase_purchase::agreement');
    }

    public function forceDeleteAny(AuthUser $authUser, Requisition $requisition): bool
    {
        return $authUser->can('force_delete_any_purchase_purchase::agreement');
    }
}
