<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Scrap;

class ScrapPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Scrap $scrap): bool
    {
        return $authUser->can('view_any_inventory_scrap');
    }

    public function view(AuthUser $authUser, Scrap $scrap): bool
    {
        return $authUser->can('view_inventory_scrap');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_scrap');
    }

    public function update(AuthUser $authUser, Scrap $scrap): bool
    {
        return $authUser->can('update_inventory_scrap');
    }

    public function delete(AuthUser $authUser, Scrap $scrap): bool
    {
        return $authUser->can('delete_inventory_scrap');
    }

    public function deleteAny(AuthUser $authUser, Scrap $scrap): bool
    {
        return $authUser->can('delete_any_inventory_scrap');
    }
}
