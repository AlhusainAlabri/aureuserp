<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\CreditNote;

class CreditNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, CreditNote $creditNote): bool
    {
        return $authUser->can('view_any_invoice_credit::note');
    }

    public function view(AuthUser $authUser, CreditNote $creditNote): bool
    {
        return $authUser->can('view_invoice_credit::note');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_credit::note');
    }

    public function update(AuthUser $authUser, CreditNote $creditNote): bool
    {
        return $authUser->can('update_invoice_credit::note');
    }

    public function delete(AuthUser $authUser, CreditNote $creditNote): bool
    {
        return $authUser->can('delete_invoice_credit::note');
    }

    public function deleteAny(AuthUser $authUser, CreditNote $creditNote): bool
    {
        return $authUser->can('delete_any_invoice_credit::note');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_invoice_credit::note');
    }
}
