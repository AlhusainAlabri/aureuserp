<?php

namespace App\Services\Projects;

use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Project;

class ProjectCompletionService
{
    public function calculate(Project $project): float
    {
        $tasksQuery = $project->tasks()->whereNull('parent_id');
        $totalTasks = (clone $tasksQuery)->where('state', '!=', TaskState::CANCELLED->value)->count();

        if ($totalTasks > 0) {
            $doneTasks = (clone $tasksQuery)->where('state', TaskState::DONE->value)->count();

            return round(($doneTasks / $totalTasks) * 100, 1);
        }

        $totalMilestones = $project->milestones()->count();

        if ($totalMilestones > 0) {
            $completedMilestones = $project->milestones()->where('is_completed', true)->count();

            return round(($completedMilestones / $totalMilestones) * 100, 1);
        }

        return 0.0;
    }

    public function formatPercentage(float $percentage): string
    {
        return number_format($percentage, 1).'%';
    }
}
