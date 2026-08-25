<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\SchemaMemo;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Employee\Models\EmployeeWarning;

class WarningsIssuedWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 15;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.warnings_issued');
    }

    protected function getStats(): array
    {
        try {
            if (! SchemaMemo::hasTable('employees_employee_warnings')
                || ! class_exists(EmployeeWarning::class)) {
                return [
                    Stat::make(__('dashboard.widgets.warnings_issued'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $warningsThisMonth = EmployeeWarning::query()
                ->whereMonth('issued_at', now()->month)
                ->whereYear('issued_at', now()->year)
                ->count();

            $unacknowledged = EmployeeWarning::query()
                ->where('is_acknowledged', false)
                ->whereMonth('issued_at', now()->month)
                ->whereYear('issued_at', now()->year)
                ->count();

            return [
                Stat::make(__('dashboard.stats.warnings_this_month'), $warningsThisMonth)
                    ->color($warningsThisMonth > 0 ? 'warning' : 'success')
                    ->icon('heroicon-o-exclamation-triangle'),
                Stat::make(__('dashboard.stats.high_severity'), $unacknowledged)
                    ->color($unacknowledged > 0 ? 'danger' : 'success')
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
