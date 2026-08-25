<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\Dashboard\DashboardMetricCache;
use App\Support\SchemaMemo;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Payroll\Enums\BatchStatus;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\Payslip;

class PayrollSummaryWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 7;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('hr_manager')
            || $user->hasRole('finance_manager')
            || $user->hasRole('general_manager');
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.payroll_summary');
    }

    protected function getStats(): array
    {
        try {
            if (! SchemaMemo::hasTable('payroll_batches')) {
                return [
                    Stat::make(__('dashboard.widgets.payroll_summary'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $year = (int) now()->year;
            $month = (int) now()->month;

            $metrics = DashboardMetricCache::remember("payroll_summary.{$year}.{$month}", function () use ($year, $month): array {
                $latestBatch = PayrollBatch::query()
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->latest('id')
                    ->first();

                return [
                    'total_payroll'      => $latestBatch ? (float) $latestBatch->total_net : 0.0,
                    'employees_paid'     => Payslip::query()
                        ->where('period_year', $year)
                        ->where('period_month', $month)
                        ->where('status', PayslipStatus::Paid)
                        ->count(),
                    'pending_batches'    => PayrollBatch::query()
                        ->whereNotIn('status', [BatchStatus::Paid, BatchStatus::Posted, BatchStatus::Cancelled])
                        ->count(),
                    'active_loans'       => Loan::query()->where('status', LoanStatus::Active)->count(),
                    'loan_outstanding'   => (float) Loan::query()
                        ->where('status', LoanStatus::Active)
                        ->sum('amount_remaining'),
                ];
            });

            return [
                Stat::make(__('dashboard.stats.total_payroll'), SalaryComponentResource::formatMoney($metrics['total_payroll']))
                    ->color('primary')
                    ->icon('heroicon-o-banknotes'),
                Stat::make(__('dashboard.stats.employees_paid'), $metrics['employees_paid'])
                    ->color('success')
                    ->icon('heroicon-o-users'),
                Stat::make(__('dashboard.stats.pending_batches'), $metrics['pending_batches'])
                    ->color('warning')
                    ->icon('heroicon-o-calendar-days'),
                Stat::make(
                    __('dashboard.stats.active_loans'),
                    $metrics['active_loans'].' / '.SalaryComponentResource::formatMoney($metrics['loan_outstanding']),
                )
                    ->color('info')
                    ->icon('heroicon-o-credit-card'),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.payroll_summary'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
