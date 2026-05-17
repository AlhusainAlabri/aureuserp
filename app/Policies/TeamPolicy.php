<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Security\Models\Team;

class TeamPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Team $team): bool
    {
        return $authUser->can('view_any_security_team');
    }

    public function view(AuthUser $authUser, Team $team): bool
    {
        return $authUser->can('view_security_team');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_security_team');
    }

    public function update(AuthUser $authUser, Team $team): bool
    {
        return $authUser->can('update_security_team');
    }

    public function delete(AuthUser $authUser, Team $team): bool
    {
        return $authUser->can('delete_security_team');
    }

    public function deleteAny(AuthUser $authUser, Team $team): bool
    {
        return $authUser->can('delete_any_security_team');
    }
}
