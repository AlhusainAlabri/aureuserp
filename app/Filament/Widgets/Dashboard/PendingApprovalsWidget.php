<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\ConfiguresClickableStat;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\Dashboard\DashboardMetricCache;
use App\Support\Dashboard\DashboardNavigation;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Wezlo\FilamentApproval\Enums\ApprovalStatus;
use Wezlo\FilamentApproval\Models\Approval;

class PendingApprovalsWidget extends BaseWidget
{
    use ConfiguresClickableStat;
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.pending_approvals');
    }

    protected function getStats(): array
    {
        try {
            if (! Schema::hasTable('approvals')) {
                return [
                    Stat::make(__('dashboard.widgets.pending_approvals'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $counts = DashboardMetricCache::remember('pending_approvals', function (): array {
                $query = Approval::query()->where('status', ApprovalStatus::Pending);

                $meetingApprovals = (clone $query)
                    ->where('approvable_type', 'Webkul\Meetings\Models\Meeting')
                    ->count();

                $correspondenceApprovals = (clone $query)
                    ->where('approvable_type', 'Webkul\Correspondence\Models\Correspondence')
                    ->count();

                $otherApprovals = (clone $query)
                    ->whereNotIn('approvable_type', [
                        'Webkul\Meetings\Models\Meeting',
                        'Webkul\Correspondence\Models\Correspondence',
                    ])
                    ->count();

                return [
                    'meeting'         => $meetingApprovals,
                    'correspondence'  => $correspondenceApprovals,
                    'other'           => $otherApprovals,
                    'total'           => $meetingApprovals + $correspondenceApprovals + $otherApprovals,
                ];
            });

            $total = $counts['total'];
            $color = $total > 0 ? 'danger' : 'success';

            return [
                $this->clickableStat(
                    label: __('dashboard.stats.meeting_approvals'),
                    value: $counts['meeting'],
                    url: DashboardNavigation::meetingApprovalsUrl(),
                    description: __('dashboard.stats.total_pending', ['count' => $total]),
                    descriptionIcon: 'heroicon-m-clipboard-document-check',
                    color: $color,
                    icon: 'heroicon-o-clipboard-document-check',
                ),

                $this->clickableStat(
                    label: __('dashboard.stats.correspondence_approvals'),
                    value: $counts['correspondence'],
                    url: DashboardNavigation::correspondenceApprovalsUrl(),
                    color: $counts['correspondence'] > 0 ? 'warning' : 'success',
                    icon: 'heroicon-o-envelope',
                ),

                $this->clickableStat(
                    label: __('dashboard.widgets.pending_approvals'),
                    value: $total,
                    url: DashboardNavigation::meetingApprovalsUrl() ?? DashboardNavigation::correspondenceApprovalsUrl(),
                    color: $color,
                    icon: 'heroicon-o-check-badge',
                ),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.pending_approvals'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
