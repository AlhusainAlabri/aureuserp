<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\Concerns\HasCorrespondenceVisibility;

class CorrespondenceDashboardStats extends BaseWidget
{
    use HasCorrespondenceVisibility;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'lg'      => 5,
        ];
    }

    protected function getStats(): array
    {
        $query = $this->visibleCorrespondenceQuery();
        $pendingApprovals = $this->pendingApprovalsQuery()->count();
        $outgoingThisMonth = $query->clone()->outgoing()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $incomingThisMonth = $query->clone()->incoming()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $pendingApprovalCount = $query->clone()->outgoing()->pendingApproval()->count();
        $overdueCount = $query->clone()->overdue()->count();

        $stats = [
            $this->clickableStat(
                label: __('correspondence::correspondence.dashboard.stats.outgoing_month'),
                value: $outgoingThisMonth,
                description: __('correspondence::correspondence.dashboard.stats.outgoing_hint'),
                descriptionIcon: 'heroicon-m-paper-airplane',
                color: 'primary',
                url: CorrespondenceResource::getUrl('index', ['activeTab' => 'outgoing']),
            ),
            $this->clickableStat(
                label: __('correspondence::correspondence.dashboard.stats.incoming_month'),
                value: $incomingThisMonth,
                description: __('correspondence::correspondence.dashboard.stats.incoming_hint'),
                descriptionIcon: 'heroicon-m-inbox-arrow-down',
                color: 'success',
                url: CorrespondenceResource::getUrl('index', ['activeTab' => 'incoming']),
            ),
            $this->clickableStat(
                label: __('correspondence::correspondence.dashboard.stats.pending_approval'),
                value: $pendingApprovalCount,
                description: __('correspondence::correspondence.dashboard.stats.pending_hint'),
                descriptionIcon: 'heroicon-m-clock',
                color: 'warning',
                url: CorrespondenceResource::getUrl('index', ['activeTab' => 'outgoing', 'tableFilters' => ['status' => ['value' => 'pending_approval']]]),
            ),
            $this->clickableStat(
                label: __('correspondence::correspondence.dashboard.stats.overdue'),
                value: $overdueCount,
                description: __('correspondence::correspondence.dashboard.stats.overdue_hint'),
                descriptionIcon: 'heroicon-m-exclamation-triangle',
                color: $overdueCount > 0 ? 'danger' : 'gray',
                url: CorrespondenceResource::getUrl('index', ['activeTab' => 'outgoing', 'tableFilters' => ['overdue' => ['isActive' => true]]]),
            ),
        ];

        if ($pendingApprovals > 0) {
            $stats[] = $this->clickableStat(
                label: __('correspondence::correspondence.dashboard.stats.my_approvals'),
                value: $pendingApprovals,
                description: __('correspondence::correspondence.dashboard.stats.approvals_hint'),
                descriptionIcon: 'heroicon-m-check-circle',
                color: 'info',
                url: CorrespondenceResource::getUrl('index', ['activeTab' => 'outgoing', 'tableFilters' => ['status' => ['value' => 'pending_approval']]]),
            );
        }

        return $stats;
    }

    protected function clickableStat(
        string $label,
        int|string $value,
        string $description,
        string $descriptionIcon,
        string $color,
        string $url,
    ): Stat {
        return Stat::make($label, $value)
            ->description($description)
            ->descriptionIcon($descriptionIcon, IconPosition::Before)
            ->color($color)
            ->url($url)
            ->extraAttributes(['class' => 'cursor-pointer']);
    }
}
