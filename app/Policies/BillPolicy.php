<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\Bill;

class BillPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Bill $bill): bool
    {
        return $authUser->can('view_any_invoice_bill');
    }

    public function view(AuthUser $authUser, Bill $bill): bool
    {
        return $authUser->can('view_invoice_bill');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_bill');
    }

    public function update(AuthUser $authUser, Bill $bill): bool
    {
        return $authUser->can('update_invoice_bill');
    }

    public function delete(AuthUser $authUser, Bill $bill): bool
    {
        return $authUser->can('delete_invoice_bill');
    }

    public function deleteAny(AuthUser $authUser, Bill $bill): bool
    {
        return $authUser->can('delete_any_invoice_bill');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_invoice_bill');
    }
}
