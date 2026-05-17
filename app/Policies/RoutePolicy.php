<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Route;

class RoutePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('view_any_inventory_route');
    }

    public function view(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('view_inventory_route');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_route');
    }

    public function update(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('update_inventory_route');
    }

    public function delete(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('delete_inventory_route');
    }

    public function deleteAny(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('delete_any_inventory_route');
    }

    public function restore(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('restore_inventory_route');
    }

    public function restoreAny(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('restore_any_inventory_route');
    }

    public function forceDelete(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('force_delete_inventory_route');
    }

    public function forceDeleteAny(AuthUser $authUser, Route $route): bool
    {
        return $authUser->can('force_delete_any_inventory_route');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_inventory_route');
    }
}
