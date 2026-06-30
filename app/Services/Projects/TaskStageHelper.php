<?php

namespace App\Services\Projects;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\TaskStage;

class TaskStageHelper
{
    public static function isAvailable(): bool
    {
        return Schema::hasTable('projects_task_stages');
    }

    public static function seedDefaultsForProject(Project $project): void
    {
        if (! self::isAvailable() || $project->taskStages()->exists()) {
            return;
        }

        $stageNames = [
            __('projects-extensions::stages.kanban_new'),
            __('projects-extensions::stages.kanban_in_progress'),
            __('projects-extensions::stages.kanban_review'),
            __('projects-extensions::stages.kanban_done'),
        ];

        foreach ($stageNames as $index => $name) {
            TaskStage::query()->create([
                'name'         => $name,
                'sort'         => $index + 1,
                'is_active'    => true,
                'is_collapsed' => false,
                'project_id'   => $project->id,
                'company_id'   => $project->company_id,
                'user_id'      => $project->user_id ?? Auth::id(),
                'creator_id'   => Auth::id(),
            ]);
        }
    }

    public static function resolveDefaultForProject(Project $project): ?TaskStage
    {
        if (! self::isAvailable()) {
            return null;
        }

        self::seedDefaultsForProject($project);

        $existingStage = $project->taskStages()
            ->orderBy('sort')
            ->first();

        if ($existingStage instanceof TaskStage) {
            return $existingStage;
        }

        return TaskStage::query()
            ->where('project_id', $project->id)
            ->orderBy('sort')
            ->first();
    }
}
