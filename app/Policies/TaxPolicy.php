<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\Tax;

class TaxPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Tax $tax): bool
    {
        return $authUser->can('view_any_invoice_tax');
    }

    public function view(AuthUser $authUser, Tax $tax): bool
    {
        return $authUser->can('view_invoice_tax');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_tax');
    }

    public function update(AuthUser $authUser, Tax $tax): bool
    {
        return $authUser->can('update_invoice_tax');
    }

    public function delete(AuthUser $authUser, Tax $tax): bool
    {
        return $authUser->can('delete_invoice_tax');
    }

    public function deleteAny(AuthUser $authUser, Tax $tax): bool
    {
        return $authUser->can('delete_any_invoice_tax');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_invoice_tax');
    }
}
