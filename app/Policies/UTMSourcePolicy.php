<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\UTMSource;

class UTMSourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, UTMSource $uTMSource): bool
    {
        return $authUser->can('view_any_recruitment_u::t::m::source');
    }

    public function view(AuthUser $authUser, UTMSource $uTMSource): bool
    {
        return $authUser->can('view_recruitment_u::t::m::source');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_u::t::m::source');
    }

    public function update(AuthUser $authUser, UTMSource $uTMSource): bool
    {
        return $authUser->can('update_recruitment_u::t::m::source');
    }

    public function delete(AuthUser $authUser, UTMSource $uTMSource): bool
    {
        return $authUser->can('delete_recruitment_u::t::m::source');
    }

    public function deleteAny(AuthUser $authUser, UTMSource $uTMSource): bool
    {
        return $authUser->can('delete_any_recruitment_u::t::m::source');
    }
}
