<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\ConfiguresClickableStat;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\Dashboard\DashboardMetricCache;
use App\Support\Dashboard\DashboardNavigation;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\EmployeeDocument;

class ExpiringDocumentsWidget extends BaseWidget
{
    use ConfiguresClickableStat;
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.expiring_documents');
    }

    protected function getStats(): array
    {
        try {
            if (! Schema::hasTable('employees_employee_documents')) {
                return [
                    Stat::make(__('dashboard.widgets.expiring_documents'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $counts = DashboardMetricCache::remember('expiring_documents', function (): array {
                $today = Carbon::today();
                $inSevenDays = $today->copy()->addDays(7);
                $inThirtyDays = $today->copy()->addDays(30);

                return [
                    'seven'  => EmployeeDocument::query()
                        ->whereNotNull('expiry_date')
                        ->whereBetween('expiry_date', [$today, $inSevenDays])
                        ->count(),
                    'thirty' => EmployeeDocument::query()
                        ->whereNotNull('expiry_date')
                        ->whereBetween('expiry_date', [$today, $inThirtyDays])
                        ->count(),
                    'expired' => EmployeeDocument::query()
                        ->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', $today)
                        ->count(),
                ];
            });

            $employeesUrl = DashboardNavigation::expiringDocumentsUrl();

            return [
                $this->clickableStat(
                    label: __('dashboard.stats.expiring_7_days'),
                    value: $counts['seven'],
                    url: $employeesUrl,
                    color: $counts['seven'] > 0 ? 'warning' : 'success',
                    icon: 'heroicon-o-document-check',
                ),
                $this->clickableStat(
                    label: __('dashboard.stats.expiring_30_days'),
                    value: $counts['thirty'],
                    url: $employeesUrl,
                    color: $counts['thirty'] > 0 ? 'warning' : 'success',
                    icon: 'heroicon-o-document',
                ),
                $this->clickableStat(
                    label: __('dashboard.stats.already_expired'),
                    value: $counts['expired'],
                    url: $employeesUrl,
                    color: $counts['expired'] > 0 ? 'danger' : 'success',
                    icon: 'heroicon-o-document-minus',
                ),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.expiring_documents'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
