<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\Product;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('view_any_sale_product');
    }

    public function view(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('view_sale_product');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_product');
    }

    public function update(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('update_sale_product');
    }

    public function delete(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('delete_sale_product');
    }

    public function deleteAny(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('delete_any_sale_product');
    }

    public function restore(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('restore_sale_product');
    }

    public function restoreAny(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('restore_any_sale_product');
    }

    public function forceDelete(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('force_delete_sale_product');
    }

    public function forceDeleteAny(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('force_delete_any_sale_product');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_sale_product');
    }
}
