<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Support\Models\State;

class StatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('view_any_support_state');
    }

    public function view(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('view_support_state');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_support_state');
    }

    public function update(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('update_support_state');
    }

    public function delete(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('delete_support_state');
    }

    public function deleteAny(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('delete_any_support_state');
    }
}
