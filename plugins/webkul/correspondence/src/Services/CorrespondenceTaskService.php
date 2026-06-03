<?php

namespace Webkul\Correspondence\Services;

use App\Services\Projects\TaskStatePresenter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Security\Models\User;

class CorrespondenceTaskService
{
    public static function isAvailable(): bool
    {
        return Schema::hasTable('projects_tasks')
            && class_exists(Task::class);
    }

    /**
     * @param  array{title: string, description?: string|null, deadline?: string|null, assignee_id?: int|null, project_id?: int|null}  $data
     */
    public static function createFromCorrespondence(Correspondence $correspondence, array $data): ?Task
    {
        if (! self::isAvailable()) {
            return null;
        }

        $projectId = $data['project_id'] ?? $correspondence->project_id;
        $stageId = self::resolveStageId($projectId);

        if (! $stageId) {
            return null;
        }

        $task = Task::query()->create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'deadline'    => $data['deadline'] ?? $correspondence->due_date,
            'project_id'  => $projectId,
            'stage_id'    => $stageId,
            'state'       => TaskStatePresenter::defaultState(),
            'company_id'  => $correspondence->company_id,
            'is_active'   => true,
        ]);

        $task->forceFill(['correspondence_id' => $correspondence->id])->saveQuietly();

        if (! empty($data['assignee_id'])) {
            $task->users()->sync([$data['assignee_id']]);

            $assignee = User::query()->find($data['assignee_id']);

            if ($assignee) {
                Notification::make()
                    ->title(__('correspondence::correspondence.tasks.created'))
                    ->body($correspondence->reference_number.' — '.$task->title)
                    ->success()
                    ->sendToDatabase($assignee);
            }
        }

        return $task;
    }

    protected static function resolveStageId(?int $projectId): ?int
    {
        if ($projectId) {
            $projectStage = TaskStage::query()
                ->where('project_id', $projectId)
                ->orderBy('sort')
                ->value('id');

            if ($projectStage) {
                return $projectStage;
            }
        }

        return TaskStage::query()
            ->whereNull('project_id')
            ->orderBy('sort')
            ->value('id')
            ?? TaskStage::query()->orderBy('sort')->value('id');
    }
}
