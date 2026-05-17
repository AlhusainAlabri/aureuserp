<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Project\Models\TaskStage;

class TaskStagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('view_any_project_task::stage');
    }

    public function view(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('view_project_task::stage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project_task::stage');
    }

    public function update(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('update_project_task::stage');
    }

    public function delete(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('delete_project_task::stage');
    }

    public function deleteAny(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('delete_any_project_task::stage');
    }

    public function restore(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('restore_project_task::stage');
    }

    public function restoreAny(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('restore_any_project_task::stage');
    }

    public function forceDelete(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('force_delete_project_task::stage');
    }

    public function forceDeleteAny(AuthUser $authUser, TaskStage $taskStage): bool
    {
        return $authUser->can('force_delete_any_project_task::stage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_project_task::stage');
    }
}
