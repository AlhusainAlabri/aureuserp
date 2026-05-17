<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Product\Models\PriceList;

class PriceListPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('view_any_product_price::list');
    }

    public function view(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('view_product_price::list');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_product_price::list');
    }

    public function update(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('update_product_price::list');
    }

    public function delete(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('delete_product_price::list');
    }

    public function deleteAny(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('delete_any_product_price::list');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_product_price::list');
    }
}
