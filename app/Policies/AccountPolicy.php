<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Accounting\Models\Account;

class AccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Account $account): bool
    {
        return $authUser->can('view_any_accounting_account');
    }

    public function view(AuthUser $authUser, Account $account): bool
    {
        return $authUser->can('view_accounting_account');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_accounting_account');
    }

    public function update(AuthUser $authUser, Account $account): bool
    {
        return $authUser->can('update_accounting_account');
    }

    public function delete(AuthUser $authUser, Account $account): bool
    {
        return $authUser->can('delete_accounting_account');
    }

    public function deleteAny(AuthUser $authUser, Account $account): bool
    {
        return $authUser->can('delete_any_accounting_account');
    }
}
