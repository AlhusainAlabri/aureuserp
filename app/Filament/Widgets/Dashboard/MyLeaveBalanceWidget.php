<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class MyLeaveBalanceWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 20;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.my_leave');
    }

    protected function getStats(): array
    {
        try {
            $hasTable = Schema::hasTable('hr_leave_allocations') || Schema::hasTable('time_off_allocations');

            if (! $hasTable) {
                return [
                    Stat::make(__('dashboard.widgets.my_leave'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            return [
                Stat::make(__('dashboard.stats.annual_leave'), __('dashboard.stats.days_remaining', ['days' => '—']))
                    ->color('info')
                    ->icon('heroicon-o-sun'),
                Stat::make(__('dashboard.stats.sick_leave'), __('dashboard.stats.days_remaining', ['days' => '—']))
                    ->color('info')
                    ->icon('heroicon-o-heart'),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.my_leave'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
