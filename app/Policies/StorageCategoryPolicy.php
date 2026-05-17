<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\StorageCategory;

class StorageCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, StorageCategory $storageCategory): bool
    {
        return $authUser->can('view_any_inventory_storage::category');
    }

    public function view(AuthUser $authUser, StorageCategory $storageCategory): bool
    {
        return $authUser->can('view_inventory_storage::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_storage::category');
    }

    public function update(AuthUser $authUser, StorageCategory $storageCategory): bool
    {
        return $authUser->can('update_inventory_storage::category');
    }

    public function delete(AuthUser $authUser, StorageCategory $storageCategory): bool
    {
        return $authUser->can('delete_inventory_storage::category');
    }

    public function deleteAny(AuthUser $authUser, StorageCategory $storageCategory): bool
    {
        return $authUser->can('delete_any_inventory_storage::category');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_inventory_storage::category');
    }
}
