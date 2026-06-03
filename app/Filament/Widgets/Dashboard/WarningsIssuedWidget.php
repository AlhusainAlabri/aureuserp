<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class WarningsIssuedWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 15;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.warnings_issued');
    }

    protected function getStats(): array
    {
        try {
            if (! Schema::hasTable('employee_warnings')) {
                return [
                    Stat::make(__('dashboard.widgets.warnings_issued'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            return [
                Stat::make(__('dashboard.stats.warnings_this_month'), 0)
                    ->color('success')
                    ->icon('heroicon-o-exclamation-triangle'),
                Stat::make(__('dashboard.stats.high_severity'), 0)
                    ->color('success')
                    ->icon('heroicon-o-shield-exclamation'),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.warnings_issued'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
