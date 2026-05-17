<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Dropship;

class DropshipPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Dropship $dropship): bool
    {
        return $authUser->can('view_any_inventory_dropship');
    }

    public function view(AuthUser $authUser, Dropship $dropship): bool
    {
        return $authUser->can('view_inventory_dropship');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_dropship');
    }

    public function update(AuthUser $authUser, Dropship $dropship): bool
    {
        return $authUser->can('update_inventory_dropship');
    }

    public function delete(AuthUser $authUser, Dropship $dropship): bool
    {
        return $authUser->can('delete_inventory_dropship');
    }

    public function deleteAny(AuthUser $authUser, Dropship $dropship): bool
    {
        return $authUser->can('delete_any_inventory_dropship');
    }
}
