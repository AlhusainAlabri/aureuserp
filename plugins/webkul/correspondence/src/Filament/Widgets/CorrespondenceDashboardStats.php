<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Correspondence\Filament\Widgets\Concerns\HasCorrespondenceVisibility;

class CorrespondenceDashboardStats extends BaseWidget
{
    use HasCorrespondenceVisibility;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $query = $this->visibleCorrespondenceQuery();
        $pendingApprovals = $this->pendingApprovalsQuery()->count();

        return [
            Stat::make(__('correspondence::correspondence.dashboard.stats.outgoing_month'), $query->clone()->outgoing()->whereMonth('created_at', now()->month)->count())
                ->descriptionIcon('heroicon-o-paper-airplane')
                ->color('primary'),
            Stat::make(__('correspondence::correspondence.dashboard.stats.incoming_month'), $query->clone()->incoming()->whereMonth('created_at', now()->month)->count())
                ->descriptionIcon('heroicon-o-inbox-arrow-down')
                ->color('success'),
            Stat::make(__('correspondence::correspondence.dashboard.stats.pending_approval'), $query->clone()->outgoing()->pendingApproval()->count())
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make(__('correspondence::correspondence.dashboard.stats.overdue'), $query->clone()->overdue()->count())
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($query->clone()->overdue()->exists() ? 'danger' : 'gray'),
            Stat::make(__('correspondence::correspondence.dashboard.stats.my_approvals'), $pendingApprovals)
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info'),
        ];
    }
}
