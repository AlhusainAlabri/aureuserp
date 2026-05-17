<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\OrderToUpsell;

class OrderToUpsellPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('view_any_sale_order::to::upsell');
    }

    public function view(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('view_sale_order::to::upsell');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_order::to::upsell');
    }

    public function update(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('update_sale_order::to::upsell');
    }

    public function delete(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('delete_sale_order::to::upsell');
    }

    public function deleteAny(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('delete_any_sale_order::to::upsell');
    }

    public function restore(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('restore_sale_order::to::upsell');
    }

    public function restoreAny(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('restore_any_sale_order::to::upsell');
    }

    public function forceDelete(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('force_delete_sale_order::to::upsell');
    }

    public function forceDeleteAny(AuthUser $authUser, OrderToUpsell $orderToUpsell): bool
    {
        return $authUser->can('force_delete_any_sale_order::to::upsell');
    }
}
