<?php

namespace Webkul\MyNotes\Policies;

use Webkul\MyNotes\Models\Note;
use Webkul\Security\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function restore(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }
}
