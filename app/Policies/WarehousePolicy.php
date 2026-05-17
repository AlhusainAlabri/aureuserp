<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Warehouse;

class WarehousePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('view_any_inventory_warehouse');
    }

    public function view(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('view_inventory_warehouse');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_warehouse');
    }

    public function update(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('update_inventory_warehouse');
    }

    public function delete(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('delete_inventory_warehouse');
    }

    public function deleteAny(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('delete_any_inventory_warehouse');
    }

    public function restore(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('restore_inventory_warehouse');
    }

    public function restoreAny(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('restore_any_inventory_warehouse');
    }

    public function forceDelete(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('force_delete_inventory_warehouse');
    }

    public function forceDeleteAny(AuthUser $authUser, Warehouse $warehouse): bool
    {
        return $authUser->can('force_delete_any_inventory_warehouse');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_inventory_warehouse');
    }
}
