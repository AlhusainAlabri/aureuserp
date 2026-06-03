<?php

namespace App\Filament\Projects\Widgets;

use App\Filament\Projects\Widgets\Concerns\HasTaskHubWidgetLayout;
use App\Services\Projects\TaskStatePresenter;
use App\Services\Projects\UnifiedTaskQueryService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Task;

class TasksByStatusChartWidget extends ChartWidget
{
    use HasTaskHubWidgetLayout;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('tasks.stats.by_status');
    }

    public function getDescription(): ?string
    {
        if (! Schema::hasTable('projects_tasks')) {
            return null;
        }

        if (UnifiedTaskQueryService::countOpenProjectTasks() === 0) {
            return __('tasks.empty.no_open_tasks_chart_description');
        }

        return null;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    protected function emptyChartData(): array
    {
        return [
            'datasets' => [[
                'data'            => [1],
                'backgroundColor' => ['#E5E7EB'],
                'borderWidth'     => 0,
            ]],
            'labels' => [__('tasks.empty.no_open_tasks_chart')],
        ];
    }

    protected function getData(): array
    {
        if (! Schema::hasTable('projects_tasks')) {
            return [
                'datasets' => [[
                    'data'            => [1],
                    'backgroundColor' => ['#9CA3AF'],
                    'borderWidth'     => 0,
                ]],
                'labels' => [__('dashboard.plugin_not_installed')],
            ];
        }

        $chartColors = [
            TaskState::IN_PROGRESS->value      => '#3B82F6',
            TaskState::CHANGE_REQUESTED->value => '#6B7280',
            TaskState::APPROVED->value         => '#F59E0B',
            TaskState::CANCELLED->value        => '#EF4444',
            TaskState::DONE->value             => '#10B981',
        ];

        $counts = [];
        $labels = [];
        $colors = [];

        foreach (TaskStatePresenter::options() as $state => $label) {
            $count = UnifiedTaskQueryService::openProjectTasksQuery()
                ->where('state', $state)
                ->count();

            if ($count === 0) {
                continue;
            }

            $counts[] = $count;
            $labels[] = $label;
            $colors[] = $chartColors[$state] ?? '#9CA3AF';
        }

        $closedDone = Task::query()
            ->whereNull('parent_id')
            ->where('state', TaskState::DONE)
            ->count();

        if ($closedDone > 0 && $counts === []) {
            $counts[] = $closedDone;
            $labels[] = TaskState::DONE->getLabel();
            $colors[] = $chartColors[TaskState::DONE->value];
        }

        if ($counts === []) {
            return $this->emptyChartData();
        }

        return [
            'datasets' => [[
                'label'           => __('tasks.stats.by_status'),
                'data'            => $counts,
                'backgroundColor' => $colors,
                'borderWidth'     => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => true,
        ];
    }
}
