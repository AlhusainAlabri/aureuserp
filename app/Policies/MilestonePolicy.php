<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Project\Models\Milestone;

class MilestonePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Milestone $milestone): bool
    {
        return $authUser->can('view_any_project_milestone');
    }

    public function view(AuthUser $authUser, Milestone $milestone): bool
    {
        return $authUser->can('view_project_milestone');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project_milestone');
    }

    public function update(AuthUser $authUser, Milestone $milestone): bool
    {
        return $authUser->can('update_project_milestone');
    }

    public function delete(AuthUser $authUser, Milestone $milestone): bool
    {
        return $authUser->can('delete_project_milestone');
    }

    public function deleteAny(AuthUser $authUser, Milestone $milestone): bool
    {
        return $authUser->can('delete_any_project_milestone');
    }
}
