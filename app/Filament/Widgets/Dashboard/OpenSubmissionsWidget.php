<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class OpenSubmissionsWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 16;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.open_submissions');
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $roles = $user->roles->pluck('name')->map(fn ($n) => strtolower((string) $n));

        return $roles->contains('super_admin')
            || $roles->contains('general_manager')
            || $roles->contains('hr_manager');
    }

    protected function getStats(): array
    {
        try {
            if (! Schema::hasTable('employee_submissions')) {
                return [
                    Stat::make(__('dashboard.widgets.open_submissions'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            return [
                Stat::make(__('dashboard.stats.open_submissions'), 0)
                    ->color('success')
                    ->icon('heroicon-o-inbox'),
                Stat::make(__('dashboard.stats.under_review'), 0)
                    ->color('success')
                    ->icon('heroicon-o-eye'),
                Stat::make(__('dashboard.stats.this_month_total'), 0)
                    ->color('info')
                    ->icon('heroicon-o-chart-bar'),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.open_submissions'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
