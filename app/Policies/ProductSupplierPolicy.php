<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Purchase\Models\ProductSupplier;

class ProductSupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, ProductSupplier $productSupplier): bool
    {
        return $authUser->can('view_any_purchase_vendor::price');
    }

    public function view(AuthUser $authUser, ProductSupplier $productSupplier): bool
    {
        return $authUser->can('view_purchase_vendor::price');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_purchase_vendor::price');
    }

    public function update(AuthUser $authUser, ProductSupplier $productSupplier): bool
    {
        return $authUser->can('update_purchase_vendor::price');
    }

    public function delete(AuthUser $authUser, ProductSupplier $productSupplier): bool
    {
        return $authUser->can('delete_purchase_vendor::price');
    }

    public function deleteAny(AuthUser $authUser, ProductSupplier $productSupplier): bool
    {
        return $authUser->can('delete_any_purchase_vendor::price');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_purchase_vendor::price');
    }
}
