<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Delivery;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser, Delivery $delivery): bool
    {
        return $authUser->can('view_any_inventory_delivery');
    }

    public function view(AuthUser $authUser, Delivery $delivery): bool
    {
        return $authUser->can('view_inventory_delivery');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_delivery');
    }

    public function update(AuthUser $authUser, Delivery $delivery): bool
    {
        return $authUser->can('update_inventory_delivery');
    }

    public function delete(AuthUser $authUser, Delivery $delivery): bool
    {
        return $authUser->can('delete_inventory_delivery');
    }

    public function deleteAny(AuthUser $authUser, Delivery $delivery): bool
    {
        return $authUser->can('delete_any_inventory_delivery');
    }

}