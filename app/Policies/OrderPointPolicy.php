<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\OrderPoint;

class OrderPointPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('view_any_inventory_replenishment');
    }

    public function view(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('view_inventory_replenishment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_replenishment');
    }

    public function update(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('update_inventory_replenishment');
    }

    public function delete(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('delete_inventory_replenishment');
    }

    public function deleteAny(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('delete_any_inventory_replenishment');
    }

    public function restore(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('restore_inventory_replenishment');
    }

    public function restoreAny(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('restore_any_inventory_replenishment');
    }

    public function forceDelete(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('force_delete_inventory_replenishment');
    }

    public function forceDeleteAny(AuthUser $authUser, OrderPoint $orderPoint): bool
    {
        return $authUser->can('force_delete_any_inventory_replenishment');
    }
}
