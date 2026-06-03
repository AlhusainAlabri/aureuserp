<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class PendingLeaveRequestsWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 17;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.leave_requests');
    }

    protected function getStats(): array
    {
        try {
            $hasTable = Schema::hasTable('hr_leave_allocations') || Schema::hasTable('time_off_allocations');

            if (! $hasTable) {
                return [
                    Stat::make(__('dashboard.widgets.leave_requests'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            return [
                Stat::make(__('dashboard.stats.pending_requests'), 0)
                    ->color('success')
                    ->icon('heroicon-o-calendar-days'),
                Stat::make(__('dashboard.stats.approved_today'), 0)
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.leave_requests'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
