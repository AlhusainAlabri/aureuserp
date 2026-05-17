<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\PackageType;

class PackageTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, PackageType $packageType): bool
    {
        return $authUser->can('view_any_inventory_package::type');
    }

    public function view(AuthUser $authUser, PackageType $packageType): bool
    {
        return $authUser->can('view_inventory_package::type');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_package::type');
    }

    public function update(AuthUser $authUser, PackageType $packageType): bool
    {
        return $authUser->can('update_inventory_package::type');
    }

    public function delete(AuthUser $authUser, PackageType $packageType): bool
    {
        return $authUser->can('delete_inventory_package::type');
    }

    public function deleteAny(AuthUser $authUser, PackageType $packageType): bool
    {
        return $authUser->can('delete_any_inventory_package::type');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_inventory_package::type');
    }
}
