<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Project\Models\ProjectStage;

class ProjectStagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('view_any_project_project::stage');
    }

    public function view(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('view_project_project::stage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project_project::stage');
    }

    public function update(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('update_project_project::stage');
    }

    public function delete(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('delete_project_project::stage');
    }

    public function deleteAny(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('delete_any_project_project::stage');
    }

    public function restore(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('restore_project_project::stage');
    }

    public function restoreAny(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('restore_any_project_project::stage');
    }

    public function forceDelete(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('force_delete_project_project::stage');
    }

    public function forceDeleteAny(AuthUser $authUser, ProjectStage $projectStage): bool
    {
        return $authUser->can('force_delete_any_project_project::stage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_project_project::stage');
    }
}
