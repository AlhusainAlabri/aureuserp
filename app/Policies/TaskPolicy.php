<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Project\Models\Task;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('view_any_project_task');
    }

    public function view(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('view_project_task');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project_task');
    }

    public function update(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('update_project_task');
    }

    public function delete(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('delete_project_task');
    }

    public function deleteAny(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('delete_any_project_task');
    }

    public function restore(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('restore_project_task');
    }

    public function restoreAny(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('restore_any_project_task');
    }

    public function forceDelete(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('force_delete_project_task');
    }

    public function forceDeleteAny(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('force_delete_any_project_task');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_project_task');
    }
}
