<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Support\Models\UOMCategory;

class UOMCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, UOMCategory $uOMCategory): bool
    {
        return $authUser->can('view_any_support_u::o::m::category');
    }

    public function view(AuthUser $authUser, UOMCategory $uOMCategory): bool
    {
        return $authUser->can('view_support_u::o::m::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_support_u::o::m::category');
    }

    public function update(AuthUser $authUser, UOMCategory $uOMCategory): bool
    {
        return $authUser->can('update_support_u::o::m::category');
    }

    public function delete(AuthUser $authUser, UOMCategory $uOMCategory): bool
    {
        return $authUser->can('delete_support_u::o::m::category');
    }

    public function deleteAny(AuthUser $authUser, UOMCategory $uOMCategory): bool
    {
        return $authUser->can('delete_any_support_u::o::m::category');
    }
}
