<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Accounting\Models\FiscalPosition;

class FiscalPositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, FiscalPosition $fiscalPosition): bool
    {
        return $authUser->can('view_any_accounting_fiscal::position');
    }

    public function view(AuthUser $authUser, FiscalPosition $fiscalPosition): bool
    {
        return $authUser->can('view_accounting_fiscal::position');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_accounting_fiscal::position');
    }

    public function update(AuthUser $authUser, FiscalPosition $fiscalPosition): bool
    {
        return $authUser->can('update_accounting_fiscal::position');
    }

    public function delete(AuthUser $authUser, FiscalPosition $fiscalPosition): bool
    {
        return $authUser->can('delete_accounting_fiscal::position');
    }

    public function deleteAny(AuthUser $authUser, FiscalPosition $fiscalPosition): bool
    {
        return $authUser->can('delete_any_accounting_fiscal::position');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_accounting_fiscal::position');
    }
}
