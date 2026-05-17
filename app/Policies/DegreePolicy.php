<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\Degree;

class DegreePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Degree $degree): bool
    {
        return $authUser->can('view_any_recruitment_degree');
    }

    public function view(AuthUser $authUser, Degree $degree): bool
    {
        return $authUser->can('view_recruitment_degree');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_degree');
    }

    public function update(AuthUser $authUser, Degree $degree): bool
    {
        return $authUser->can('update_recruitment_degree');
    }

    public function delete(AuthUser $authUser, Degree $degree): bool
    {
        return $authUser->can('delete_recruitment_degree');
    }

    public function deleteAny(AuthUser $authUser, Degree $degree): bool
    {
        return $authUser->can('delete_any_recruitment_degree');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_recruitment_degree');
    }
}
