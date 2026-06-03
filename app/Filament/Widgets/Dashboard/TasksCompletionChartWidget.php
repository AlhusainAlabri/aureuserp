<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use App\Services\Projects\UnifiedTaskQueryService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Task;

class TasksCompletionChartWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 21;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.charts.tasks_completion');
    }

    protected function getData(): array
    {
        if (! UnifiedTaskQueryService::projectTasksAvailable()) {
            return $this->emptyPluginData();
        }

        $startDate = $this->pageFilters['startDate'] ?? null;
        $endDate = $this->pageFilters['endDate'] ?? null;

        $openQuery = Task::query()
            ->whereNull('parent_id')
            ->whereNotIn('state', [TaskState::DONE, TaskState::CANCELLED]);

        $completedQuery = Task::query()
            ->whereNull('parent_id')
            ->where('state', TaskState::DONE);

        if (filled($startDate)) {
            $openQuery->whereDate('created_at', '>=', $startDate);
            $completedQuery->whereDate('updated_at', '>=', $startDate);
        }

        if (filled($endDate)) {
            $openQuery->whereDate('created_at', '<=', $endDate);
            $completedQuery->whereDate('updated_at', '<=', $endDate);
        }

        return [
            'datasets' => [[
                'label'           => __('dashboard.charts.task_count'),
                'data'            => [$openQuery->count(), $completedQuery->count()],
                'backgroundColor' => ['#F97316', '#22C55E'],
            ]],
            'labels' => [
                __('dashboard.charts.tasks_open'),
                __('dashboard.charts.tasks_completed'),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    protected function emptyPluginData(): array
    {
        return [
            'datasets' => [[
                'data'            => [1],
                'backgroundColor' => ['#9CA3AF'],
            ]],
            'labels' => [__('dashboard.plugin_not_installed')],
        ];
    }
}
