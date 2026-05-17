<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Accounting\Models\Journal;

class JournalPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('view_any_accounting_journal');
    }

    public function view(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('view_accounting_journal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_accounting_journal');
    }

    public function update(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('update_accounting_journal');
    }

    public function delete(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('delete_accounting_journal');
    }

    public function deleteAny(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('delete_any_accounting_journal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_accounting_journal');
    }
}
