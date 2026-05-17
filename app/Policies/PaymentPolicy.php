<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\Payment;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Payment $payment): bool
    {
        return $authUser->can('view_any_invoice_payment');
    }

    public function view(AuthUser $authUser, Payment $payment): bool
    {
        return $authUser->can('view_invoice_payment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_payment');
    }

    public function update(AuthUser $authUser, Payment $payment): bool
    {
        return $authUser->can('update_invoice_payment');
    }

    public function delete(AuthUser $authUser, Payment $payment): bool
    {
        return $authUser->can('delete_invoice_payment');
    }

    public function deleteAny(AuthUser $authUser, Payment $payment): bool
    {
        return $authUser->can('delete_any_invoice_payment');
    }
}
