<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\SchemaMemo;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\TimeOff\Enums\State;
use Webkul\TimeOff\Models\Leave;

class PendingLeaveRequestsWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 17;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.leave_requests');
    }

    protected function getStats(): array
    {
        try {
            if (! SchemaMemo::hasTable('time_off_leaves')
                || ! class_exists(Leave::class)) {
                return [
                    Stat::make(__('dashboard.widgets.leave_requests'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $pending = Leave::query()
                ->whereIn('state', [
                    State::CONFIRM->value,
                    State::VALIDATE_ONE->value,
                ])
                ->count();

            $approvedToday = Leave::query()
                ->where('state', State::VALIDATE_TWO->value)
                ->whereDate('updated_at', today())
                ->count();

            return [
                Stat::make(__('dashboard.stats.pending_requests'), $pending)
                    ->color($pending > 0 ? 'warning' : 'success')
                    ->icon('heroicon-o-calendar-days'),
                Stat::make(__('dashboard.stats.approved_today'), $approvedToday)
                    ->color($approvedToday > 0 ? 'success' : 'gray')
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
