<?php

namespace App\Filament\Projects\Widgets;

use App\Services\Projects\UnifiedTaskQueryService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Project\Filament\Resources\TaskResource;

class TaskOverviewStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        if (! UnifiedTaskQueryService::projectTasksAvailable()) {
            return [
                Stat::make(__('tasks.stats.open'), __('dashboard.plugin_not_installed'))
                    ->color('gray'),
            ];
        }

        $userId = auth()->id();
        $overdue = UnifiedTaskQueryService::countOverdueProjectTasks($userId);
        $dueToday = UnifiedTaskQueryService::countDueTodayProjectTasks($userId);

        return [
            Stat::make(__('tasks.stats.open'), UnifiedTaskQueryService::countOpenProjectTasks())
                ->description(__('tasks.hub.view_all_tasks'))
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->url(TaskResource::getUrl('index')),

            Stat::make(__('tasks.stats.overdue'), $overdue)
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'success'),

            Stat::make(__('tasks.stats.due_today'), $dueToday)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($dueToday > 0 ? 'warning' : 'gray'),

            Stat::make(__('tasks.stats.completed_week'), UnifiedTaskQueryService::countCompletedThisWeek())
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
