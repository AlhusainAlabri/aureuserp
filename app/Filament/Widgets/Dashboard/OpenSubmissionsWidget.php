<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\SchemaMemo;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Employee\Models\EmployeeSubmission;

class OpenSubmissionsWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 16;

    protected ?string $pollingInterval = null;

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
            if (! SchemaMemo::hasTable('employees_employee_submissions')
                || ! class_exists(EmployeeSubmission::class)) {
                return [
                    Stat::make(__('dashboard.widgets.open_submissions'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $open = EmployeeSubmission::query()->open()->count();
            $underReview = EmployeeSubmission::query()->underReview()->count();
            $thisMonth = EmployeeSubmission::query()->thisMonth()->count();

            return [
                Stat::make(__('dashboard.stats.open_submissions'), $open)
                    ->color($open > 0 ? 'warning' : 'success')
                    ->icon('heroicon-o-inbox'),
                Stat::make(__('dashboard.stats.under_review'), $underReview)
                    ->color($underReview > 0 ? 'warning' : 'success')
                    ->icon('heroicon-o-eye'),
                Stat::make(__('dashboard.stats.this_month_total'), $thisMonth)
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
