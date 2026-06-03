<?php

namespace App\Observers;

use App\Services\Projects\TaskNotificationService;
use App\Services\Projects\TaskStatePresenter;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Task;

class ProjectTaskObserver
{
    public function creating(Task $task): void
    {
        if (! Schema::hasColumn('projects_tasks', 'owner_id')) {
            return;
        }

        $task->owner_id ??= auth()->id();
    }

    public function updating(Task $task): void
    {
        if (! Schema::hasColumn('projects_tasks', 'completed_at')) {
            return;
        }

        if (! $task->isDirty('state')) {
            return;
        }

        $newState = $task->state instanceof TaskState ? $task->state->value : (string) $task->state;

        if ($newState === TaskState::DONE->value) {
            $task->completed_at ??= now();
        } elseif (! TaskStatePresenter::isClosed($newState)) {
            $task->completed_at = null;
        }
    }

    public function updated(Task $task): void
    {
        if (! class_exists(TaskNotificationService::class) || ! $task->wasChanged('state')) {
            return;
        }

        app(TaskNotificationService::class)->notifyStatusChange($task);
    }
}
