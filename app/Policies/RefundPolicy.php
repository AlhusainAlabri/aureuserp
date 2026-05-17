<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\Refund;

class RefundPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Refund $refund): bool
    {
        return $authUser->can('view_any_invoice_refund');
    }

    public function view(AuthUser $authUser, Refund $refund): bool
    {
        return $authUser->can('view_invoice_refund');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_refund');
    }

    public function update(AuthUser $authUser, Refund $refund): bool
    {
        return $authUser->can('update_invoice_refund');
    }

    public function delete(AuthUser $authUser, Refund $refund): bool
    {
        return $authUser->can('delete_invoice_refund');
    }

    public function deleteAny(AuthUser $authUser, Refund $refund): bool
    {
        return $authUser->can('delete_any_invoice_refund');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_invoice_refund');
    }
}
