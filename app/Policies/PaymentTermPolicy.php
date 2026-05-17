<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\PaymentTerm;

class PaymentTermPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('view_any_invoice_payment::term');
    }

    public function view(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('view_invoice_payment::term');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_payment::term');
    }

    public function update(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('update_invoice_payment::term');
    }

    public function delete(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('delete_invoice_payment::term');
    }

    public function deleteAny(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('delete_any_invoice_payment::term');
    }

    public function restore(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('restore_invoice_payment::term');
    }

    public function restoreAny(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('restore_any_invoice_payment::term');
    }

    public function forceDelete(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('force_delete_invoice_payment::term');
    }

    public function forceDeleteAny(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('force_delete_any_invoice_payment::term');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_invoice_payment::term');
    }
}
