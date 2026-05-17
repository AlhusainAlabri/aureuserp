<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\InternalTransfer;

class InternalTransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, InternalTransfer $internalTransfer): bool
    {
        return $authUser->can('view_any_inventory_internal');
    }

    public function view(AuthUser $authUser, InternalTransfer $internalTransfer): bool
    {
        return $authUser->can('view_inventory_internal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_internal');
    }

    public function update(AuthUser $authUser, InternalTransfer $internalTransfer): bool
    {
        return $authUser->can('update_inventory_internal');
    }

    public function delete(AuthUser $authUser, InternalTransfer $internalTransfer): bool
    {
        return $authUser->can('delete_inventory_internal');
    }

    public function deleteAny(AuthUser $authUser, InternalTransfer $internalTransfer): bool
    {
        return $authUser->can('delete_any_inventory_internal');
    }
}
