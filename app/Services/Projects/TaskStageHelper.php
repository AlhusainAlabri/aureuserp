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

    public static function resolveDefaultForProject(Project $project): ?TaskStage
    {
        if (! self::isAvailable()) {
            return null;
        }

        $existingStage = $project->taskStages()
            ->orderBy('sort')
            ->first();

        if ($existingStage instanceof TaskStage) {
            return $existingStage;
        }

        $fallbackStage = TaskStage::query()
            ->where('project_id', $project->id)
            ->orderBy('sort')
            ->first();

        if ($fallbackStage instanceof TaskStage) {
            return $fallbackStage;
        }

        return TaskStage::query()->create([
            'name'         => __('projects-extensions::stages.default_task_stage'),
            'is_active'    => true,
            'is_collapsed' => false,
            'project_id'   => $project->id,
            'company_id'   => $project->company_id,
            'user_id'      => $project->user_id ?? Auth::id(),
            'creator_id'   => Auth::id(),
        ]);
    }
}
