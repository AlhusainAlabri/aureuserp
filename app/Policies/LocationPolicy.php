<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Location;

class LocationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('view_any_inventory_location');
    }

    public function view(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('view_inventory_location');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_location');
    }

    public function update(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('update_inventory_location');
    }

    public function delete(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('delete_inventory_location');
    }

    public function deleteAny(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('delete_any_inventory_location');
    }

    public function restore(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('restore_inventory_location');
    }

    public function restoreAny(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('restore_any_inventory_location');
    }

    public function forceDelete(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('force_delete_inventory_location');
    }

    public function forceDeleteAny(AuthUser $authUser, Location $location): bool
    {
        return $authUser->can('force_delete_any_inventory_location');
    }
}
