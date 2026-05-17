<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Manufacturing\Models\Lot;

class LotPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Lot $lot): bool
    {
        return $authUser->can('view_any_manufacturing_lot');
    }

    public function view(AuthUser $authUser, Lot $lot): bool
    {
        return $authUser->can('view_manufacturing_lot');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_manufacturing_lot');
    }

    public function update(AuthUser $authUser, Lot $lot): bool
    {
        return $authUser->can('update_manufacturing_lot');
    }

    public function delete(AuthUser $authUser, Lot $lot): bool
    {
        return $authUser->can('delete_manufacturing_lot');
    }

    public function deleteAny(AuthUser $authUser, Lot $lot): bool
    {
        return $authUser->can('delete_any_manufacturing_lot');
    }
}
