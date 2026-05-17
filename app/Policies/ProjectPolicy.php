<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Project\Models\Project;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('view_any_project_project');
    }

    public function view(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('view_project_project');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project_project');
    }

    public function update(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('update_project_project');
    }

    public function delete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('delete_project_project');
    }

    public function deleteAny(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('delete_any_project_project');
    }

    public function restore(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('restore_project_project');
    }

    public function restoreAny(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('restore_any_project_project');
    }

    public function forceDelete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('force_delete_project_project');
    }

    public function forceDeleteAny(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('force_delete_any_project_project');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_project_project');
    }
}
