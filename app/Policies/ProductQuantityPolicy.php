<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\ProductQuantity;

class ProductQuantityPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, ProductQuantity $productQuantity): bool
    {
        return $authUser->can('view_any_inventory_quantity');
    }

    public function view(AuthUser $authUser, ProductQuantity $productQuantity): bool
    {
        return $authUser->can('view_inventory_quantity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_quantity');
    }

    public function update(AuthUser $authUser, ProductQuantity $productQuantity): bool
    {
        return $authUser->can('update_inventory_quantity');
    }

    public function delete(AuthUser $authUser, ProductQuantity $productQuantity): bool
    {
        return $authUser->can('delete_inventory_quantity');
    }

    public function deleteAny(AuthUser $authUser, ProductQuantity $productQuantity): bool
    {
        return $authUser->can('delete_any_inventory_quantity');
    }
}
