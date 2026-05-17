<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\Packaging;

class PackagingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Packaging $packaging): bool
    {
        return $authUser->can('view_any_sale_packaging');
    }

    public function view(AuthUser $authUser, Packaging $packaging): bool
    {
        return $authUser->can('view_sale_packaging');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_packaging');
    }

    public function update(AuthUser $authUser, Packaging $packaging): bool
    {
        return $authUser->can('update_sale_packaging');
    }

    public function delete(AuthUser $authUser, Packaging $packaging): bool
    {
        return $authUser->can('delete_sale_packaging');
    }

    public function deleteAny(AuthUser $authUser, Packaging $packaging): bool
    {
        return $authUser->can('delete_any_sale_packaging');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_sale_packaging');
    }
}
