<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Support\Models\Bank;

class BankPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('view_any_support_bank');
    }

    public function view(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('view_support_bank');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_support_bank');
    }

    public function update(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('update_support_bank');
    }

    public function delete(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('delete_support_bank');
    }

    public function deleteAny(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('delete_any_support_bank');
    }

    public function restore(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('restore_support_bank');
    }

    public function restoreAny(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('restore_any_support_bank');
    }

    public function forceDelete(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('force_delete_support_bank');
    }

    public function forceDeleteAny(AuthUser $authUser, Bank $bank): bool
    {
        return $authUser->can('force_delete_any_support_bank');
    }
}
