<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Package;

class PackagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Package $package): bool
    {
        return $authUser->can('view_any_inventory_package');
    }

    public function view(AuthUser $authUser, Package $package): bool
    {
        return $authUser->can('view_inventory_package');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_package');
    }

    public function update(AuthUser $authUser, Package $package): bool
    {
        return $authUser->can('update_inventory_package');
    }

    public function delete(AuthUser $authUser, Package $package): bool
    {
        return $authUser->can('delete_inventory_package');
    }

    public function deleteAny(AuthUser $authUser, Package $package): bool
    {
        return $authUser->can('delete_any_inventory_package');
    }
}
