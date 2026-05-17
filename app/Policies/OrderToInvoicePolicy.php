<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\OrderToInvoice;

class OrderToInvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('view_any_sale_order::to::invoice');
    }

    public function view(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('view_sale_order::to::invoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_order::to::invoice');
    }

    public function update(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('update_sale_order::to::invoice');
    }

    public function delete(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('delete_sale_order::to::invoice');
    }

    public function deleteAny(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('delete_any_sale_order::to::invoice');
    }

    public function restore(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('restore_sale_order::to::invoice');
    }

    public function restoreAny(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('restore_any_sale_order::to::invoice');
    }

    public function forceDelete(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('force_delete_sale_order::to::invoice');
    }

    public function forceDeleteAny(AuthUser $authUser, OrderToInvoice $orderToInvoice): bool
    {
        return $authUser->can('force_delete_any_sale_order::to::invoice');
    }
}
