<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Accounting\Models\CashRounding;

class CashRoundingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, CashRounding $cashRounding): bool
    {
        return $authUser->can('view_any_accounting_cash::rounding');
    }

    public function view(AuthUser $authUser, CashRounding $cashRounding): bool
    {
        return $authUser->can('view_accounting_cash::rounding');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_accounting_cash::rounding');
    }

    public function update(AuthUser $authUser, CashRounding $cashRounding): bool
    {
        return $authUser->can('update_accounting_cash::rounding');
    }

    public function delete(AuthUser $authUser, CashRounding $cashRounding): bool
    {
        return $authUser->can('delete_accounting_cash::rounding');
    }

    public function deleteAny(AuthUser $authUser, CashRounding $cashRounding): bool
    {
        return $authUser->can('delete_any_accounting_cash::rounding');
    }
}
