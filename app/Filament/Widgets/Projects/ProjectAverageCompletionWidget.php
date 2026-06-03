<?php

namespace App\Filament\Widgets\Projects;

use App\Services\Projects\ProjectCompletionService;
use App\Services\Projects\ProjectStageHelper;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Models\Project;

class ProjectAverageCompletionWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 2;

    protected function getStats(): array
    {
        if (! Schema::hasTable('projects_projects')) {
            return [
                Stat::make(__('projects-extensions::widgets.average_completion'), '—')
                    ->description(__('dashboard.plugin_not_installed'))
                    ->color('gray'),
            ];
        }

        $query = Project::query();

        $status = $this->pageFilters['projectStatus'] ?? null;

        if ($status === 'active' && ProjectStageHelper::isAvailable()) {
            $query = ProjectStageHelper::applyStageFilter($query->where('is_active', true), 'in_progress');
        } elseif ($status === 'completed' && ProjectStageHelper::isAvailable()) {
            $query = ProjectStageHelper::applyStageFilter($query, 'done');
        } elseif ($status === 'cancelled' && ProjectStageHelper::isAvailable()) {
            $query = ProjectStageHelper::applyStageFilter($query, 'cancelled');
        }

        if (! empty($this->pageFilters['selectedProjects'])) {
            $query->whereIn('id', $this->pageFilters['selectedProjects']);
        }

        $projects = $query->get();
        $average = 0.0;

        if ($projects->isNotEmpty()) {
            $completionService = app(ProjectCompletionService::class);
            $total = $projects->sum(fn (Project $project): float => $completionService->calculate($project));
            $average = round($total / $projects->count(), 1);
        }

        return [
            Stat::make(
                __('projects-extensions::widgets.average_completion'),
                app(ProjectCompletionService::class)->formatPercentage($average),
            )
                ->description(__('projects-extensions::widgets.average_completion_desc'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}
