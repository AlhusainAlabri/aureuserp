<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\TaxGroup;

class TaxGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, TaxGroup $taxGroup): bool
    {
        return $authUser->can('view_any_invoice_tax::group');
    }

    public function view(AuthUser $authUser, TaxGroup $taxGroup): bool
    {
        return $authUser->can('view_invoice_tax::group');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_tax::group');
    }

    public function update(AuthUser $authUser, TaxGroup $taxGroup): bool
    {
        return $authUser->can('update_invoice_tax::group');
    }

    public function delete(AuthUser $authUser, TaxGroup $taxGroup): bool
    {
        return $authUser->can('delete_invoice_tax::group');
    }

    public function deleteAny(AuthUser $authUser, TaxGroup $taxGroup): bool
    {
        return $authUser->can('delete_any_invoice_tax::group');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_invoice_tax::group');
    }
}
