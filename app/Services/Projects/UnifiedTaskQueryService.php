<?php

namespace App\Services\Projects;

use App\Enums\Projects\TaskPriorityLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Filament\Resources\TaskResource;
use Webkul\Project\Models\Task;

class UnifiedTaskQueryService
{
    public static function projectTasksAvailable(): bool
    {
        return class_exists(Task::class) && Schema::hasTable('projects_tasks');
    }

    public static function meetingTasksAvailable(): bool
    {
        return class_exists(MeetingTask::class) && Schema::hasTable('meeting_tasks');
    }

    /** @return Builder<Task> */
    public static function openProjectTasksQuery(): Builder
    {
        return Task::query()
            ->whereNull('parent_id')
            ->whereNotIn('state', [TaskState::DONE, TaskState::CANCELLED]);
    }

    /** @return Builder<Task> */
    public static function myProjectTasksQuery(?int $userId = null): Builder
    {
        $userId ??= Auth::id();

        return self::openProjectTasksQuery()
            ->where(function (Builder $query) use ($userId): void {
                $query
                    ->whereHas('users', fn (Builder $assigneeQuery): Builder => $assigneeQuery->where('user_id', $userId))
                    ->when(
                        Schema::hasColumn('projects_tasks', 'owner_id'),
                        fn (Builder $ownerQuery): Builder => $ownerQuery->orWhere('owner_id', $userId),
                    );
            });
    }

    /** @return Builder<Task> */
    public static function overdueProjectTasksQuery(): Builder
    {
        return self::openProjectTasksQuery()
            ->whereNotNull('deadline')
            ->where('deadline', '<', now());
    }

    /** @return Builder<MeetingTask> */
    public static function overdueMeetingTasksQuery(): Builder
    {
        return MeetingTask::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public static function countOpenProjectTasks(): int
    {
        if (! self::projectTasksAvailable()) {
            return 0;
        }

        return self::openProjectTasksQuery()->count();
    }

    public static function countOverdueProjectTasks(?int $userId = null): int
    {
        if (! self::projectTasksAvailable()) {
            return 0;
        }

        $query = self::overdueProjectTasksQuery();

        if ($userId !== null) {
            $query->where(function (Builder $innerQuery) use ($userId): void {
                $innerQuery
                    ->whereHas('users', fn (Builder $assigneeQuery): Builder => $assigneeQuery->where('user_id', $userId))
                    ->when(
                        Schema::hasColumn('projects_tasks', 'owner_id'),
                        fn (Builder $ownerQuery): Builder => $ownerQuery->orWhere('owner_id', $userId),
                    );
            });
        }

        return $query->count();
    }

    public static function countDueTodayProjectTasks(?int $userId = null): int
    {
        if (! self::projectTasksAvailable()) {
            return 0;
        }

        $query = self::myProjectTasksQuery($userId)
            ->whereDate('deadline', now());

        return $query->count();
    }

    public static function countCompletedThisWeek(): int
    {
        if (! self::projectTasksAvailable()) {
            return 0;
        }

        return Task::query()
            ->whereNull('parent_id')
            ->where('state', TaskState::DONE)
            ->when(
                Schema::hasColumn('projects_tasks', 'completed_at'),
                fn (Builder $query): Builder => $query->where('completed_at', '>=', now()->startOfWeek()),
                fn (Builder $query): Builder => $query->where('updated_at', '>=', now()->startOfWeek()),
            )
            ->count();
    }

    /** @return Collection<int, object> */
    public static function myTasksToday(): Collection
    {
        $items = collect();

        if (self::projectTasksAvailable()) {
            $items = $items->merge(
                self::myProjectTasksQuery()
                    ->with(['project', 'users'])
                    ->where(function (Builder $query): void {
                        $query
                            ->whereDate('deadline', now())
                            ->orWhere(function (Builder $overdueQuery): void {
                                $overdueQuery
                                    ->whereNotNull('deadline')
                                    ->where('deadline', '<', now());
                            });
                    })
                    ->orderBy('deadline')
                    ->limit(20)
                    ->get()
                    ->map(fn (Task $task): array => [
                        'id'         => 'project-'.$task->id,
                        'title'      => $task->title,
                        'due_date'   => $task->deadline,
                        'status'     => TaskStatePresenter::label($task->state),
                        'priority'   => self::resolvePriorityLabel($task),
                        'source'     => 'project',
                        'url'        => TaskResource::getUrl('view', ['record' => $task]),
                        'is_overdue' => TaskStatePresenter::isOverdue($task),
                    ]),
            );
        }

        if (self::meetingTasksAvailable()) {
            $items = $items->merge(
                MeetingTask::query()
                    ->where('assigned_to', Auth::id())
                    ->where(function (Builder $query): void {
                        $query
                            ->whereDate('due_date', now())
                            ->orWhere(function (Builder $overdueQuery): void {
                                $overdueQuery
                                    ->whereDate('due_date', '<', now())
                                    ->whereNotIn('status', ['completed', 'cancelled']);
                            });
                    })
                    ->orderBy('due_date')
                    ->limit(20)
                    ->get()
                    ->map(fn (MeetingTask $task): array => [
                        'id'         => 'meeting-'.$task->id,
                        'title'      => $task->title,
                        'due_date'   => $task->due_date,
                        'status'     => $task->status,
                        'priority'   => $task->priority,
                        'source'     => 'meeting',
                        'url'        => null,
                        'is_overdue' => $task->due_date?->isPast() && ! in_array($task->status, ['completed', 'cancelled'], true),
                    ]),
            );
        }

        return $items->sortBy('due_date')->values();
    }

    protected static function resolvePriorityLabel(Task $task): string
    {
        if (Schema::hasColumn('projects_tasks', 'priority_level') && filled($task->priority_level)) {
            return TaskPriorityLevel::tryFrom($task->priority_level)?->getLabel()
                ?? (string) $task->priority_level;
        }

        return $task->priority ? TaskPriorityLevel::High->getLabel() : TaskPriorityLevel::Medium->getLabel();
    }
}
