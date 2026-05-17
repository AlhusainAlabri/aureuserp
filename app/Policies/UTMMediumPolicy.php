<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\UTMMedium;

class UTMMediumPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, UTMMedium $uTMMedium): bool
    {
        return $authUser->can('view_any_recruitment_u::t::m::medium');
    }

    public function view(AuthUser $authUser, UTMMedium $uTMMedium): bool
    {
        return $authUser->can('view_recruitment_u::t::m::medium');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_u::t::m::medium');
    }

    public function update(AuthUser $authUser, UTMMedium $uTMMedium): bool
    {
        return $authUser->can('update_recruitment_u::t::m::medium');
    }

    public function delete(AuthUser $authUser, UTMMedium $uTMMedium): bool
    {
        return $authUser->can('delete_recruitment_u::t::m::medium');
    }

    public function deleteAny(AuthUser $authUser, UTMMedium $uTMMedium): bool
    {
        return $authUser->can('delete_any_recruitment_u::t::m::medium');
    }
}
