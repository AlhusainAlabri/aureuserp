<?php

namespace App\Filament\Widgets\Dashboard;

use App\Enums\Hr\TrainingStatus;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Models\Hr\EmployeeTraining;
use App\Support\Dashboard\DashboardMetricCache;
use App\Support\SchemaMemo;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpiringCertificatesWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected static bool $isLazy = true;

    protected function getHeading(): ?string
    {
        return __('hr-extensions::widgets.expiring_certificates');
    }

    protected function getStats(): array
    {
        try {
            if (! SchemaMemo::hasTable('employee_trainings')) {
                return [
                    Stat::make(__('hr-extensions::widgets.expiring_certificates'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $metrics = DashboardMetricCache::remember('expiring_certificates', function (): array {
                $today = Carbon::today();
                $threshold = $today->copy()->addDays(60);

                return [
                    'expiring'  => EmployeeTraining::query()
                        ->whereNotNull('certificate_expiry_date')
                        ->whereBetween('certificate_expiry_date', [$today, $threshold])
                        ->count(),
                    'expired'   => EmployeeTraining::query()
                        ->whereNotNull('certificate_expiry_date')
                        ->where('certificate_expiry_date', '<', $today)
                        ->count(),
                    'completed' => EmployeeTraining::query()
                        ->where('status', TrainingStatus::Completed)
                        ->whereMonth('end_date', $today->month)
                        ->whereYear('end_date', $today->year)
                        ->count(),
                ];
            });

            return [
                Stat::make(__('hr-extensions::widgets.expiring_certificates'), $metrics['expiring'])
                    ->color($metrics['expiring'] > 0 ? 'warning' : 'success')
                    ->icon('heroicon-o-academic-cap'),
                Stat::make(__('hr-extensions::widgets.expired_certificates'), $metrics['expired'])
                    ->color($metrics['expired'] > 0 ? 'danger' : 'success')
                    ->icon('heroicon-o-exclamation-triangle'),
                Stat::make(__('hr-extensions::widgets.completed_trainings'), $metrics['completed'])
                    ->color('info')
                    ->icon('heroicon-o-check-badge'),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('hr-extensions::widgets.expiring_certificates'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
