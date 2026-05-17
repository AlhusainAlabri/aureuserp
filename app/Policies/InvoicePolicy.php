<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\Invoice;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->can('view_any_invoice_invoice');
    }

    public function view(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->can('view_invoice_invoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_invoice');
    }

    public function update(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->can('update_invoice_invoice');
    }

    public function delete(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->can('delete_invoice_invoice');
    }

    public function deleteAny(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->can('delete_any_invoice_invoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_invoice_invoice');
    }
}
