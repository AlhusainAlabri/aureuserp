<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Accounting\Models\JournalItem;

class JournalItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, JournalItem $journalItem): bool
    {
        return $authUser->can('view_any_accounting_journal::item');
    }

    public function view(AuthUser $authUser, JournalItem $journalItem): bool
    {
        return $authUser->can('view_accounting_journal::item');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_accounting_journal::item');
    }

    public function update(AuthUser $authUser, JournalItem $journalItem): bool
    {
        return $authUser->can('update_accounting_journal::item');
    }

    public function delete(AuthUser $authUser, JournalItem $journalItem): bool
    {
        return $authUser->can('delete_accounting_journal::item');
    }

    public function deleteAny(AuthUser $authUser, JournalItem $journalItem): bool
    {
        return $authUser->can('delete_any_accounting_journal::item');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_accounting_journal::item');
    }
}
