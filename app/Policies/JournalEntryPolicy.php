<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Accounting\Models\JournalEntry;

class JournalEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, JournalEntry $journalEntry): bool
    {
        return $authUser->can('view_any_accounting_journal::entry');
    }

    public function view(AuthUser $authUser, JournalEntry $journalEntry): bool
    {
        return $authUser->can('view_accounting_journal::entry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_accounting_journal::entry');
    }

    public function update(AuthUser $authUser, JournalEntry $journalEntry): bool
    {
        return $authUser->can('update_accounting_journal::entry');
    }

    public function delete(AuthUser $authUser, JournalEntry $journalEntry): bool
    {
        return $authUser->can('delete_accounting_journal::entry');
    }

    public function deleteAny(AuthUser $authUser, JournalEntry $journalEntry): bool
    {
        return $authUser->can('delete_any_accounting_journal::entry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_accounting_journal::entry');
    }
}
