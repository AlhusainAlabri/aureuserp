<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\Category;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('view_any_sale_product::category');
    }

    public function view(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('view_sale_product::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_product::category');
    }

    public function update(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('update_sale_product::category');
    }

    public function delete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('delete_sale_product::category');
    }

    public function deleteAny(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('delete_any_sale_product::category');
    }
}
