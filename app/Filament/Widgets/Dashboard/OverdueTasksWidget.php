<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\ConfiguresClickableStat;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Services\Projects\UnifiedTaskQueryService;
use App\Support\Dashboard\DashboardMetricCache;
use App\Support\Dashboard\DashboardNavigation;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverdueTasksWidget extends BaseWidget
{
    use ConfiguresClickableStat;
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.overdue_tasks');
    }

    protected function getStats(): array
    {
        try {
            if (! UnifiedTaskQueryService::projectTasksAvailable() && ! UnifiedTaskQueryService::meetingTasksAvailable()) {
                return [
                    Stat::make(__('dashboard.widgets.overdue_tasks'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $metrics = DashboardMetricCache::remember('overdue_tasks', function (): array {
                $projectOverdue = UnifiedTaskQueryService::countOverdueProjectTasks();
                $mine = UnifiedTaskQueryService::countOverdueProjectTasks(auth()->id());
                $meetingOverdue = UnifiedTaskQueryService::meetingTasksAvailable()
                    ? UnifiedTaskQueryService::overdueMeetingTasksQuery()->count()
                    : 0;

                return [
                    'project_overdue' => $projectOverdue,
                    'meeting_overdue' => $meetingOverdue,
                    'mine'            => $mine,
                    'open'            => UnifiedTaskQueryService::countOpenProjectTasks(),
                    'total'           => $projectOverdue + $meetingOverdue,
                ];
            });

            $total = $metrics['total'];
            $color = $total > 0 ? 'danger' : 'success';
            $taskHubUrl = DashboardNavigation::taskOperationsHubUrl();

            return [
                $this->clickableStat(
                    label: __('dashboard.stats.overdue_tasks'),
                    value: $total,
                    url: $taskHubUrl,
                    color: $color,
                    icon: 'heroicon-o-clock',
                ),

                $this->clickableStat(
                    label: __('dashboard.stats.assigned_to_me'),
                    value: $metrics['mine'],
                    url: DashboardNavigation::projectTasksUrl(),
                    color: $metrics['mine'] > 0 ? 'warning' : 'success',
                    icon: 'heroicon-o-user',
                ),

                $this->clickableStat(
                    label: __('tasks.stats.open'),
                    value: $metrics['open'],
                    url: DashboardNavigation::projectTasksUrl(),
                    color: 'info',
                    icon: 'heroicon-o-clipboard-document-list',
                ),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.overdue_tasks'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
