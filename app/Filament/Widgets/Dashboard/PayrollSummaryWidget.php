<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Enums\BatchStatus;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\Payslip;

class PayrollSummaryWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 7;

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
            if (! Schema::hasTable('payroll_batches')) {
                return [
                    Stat::make(__('dashboard.widgets.payroll_summary'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $year = (int) now()->year;
            $month = (int) now()->month;

            $latestBatch = PayrollBatch::query()
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->latest('id')
                ->first();

            $totalPayroll = $latestBatch
                ? number_format((float) $latestBatch->total_net, 3)
                : '0.000';

            $employeesPaid = Payslip::query()
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->where('status', PayslipStatus::Paid)
                ->count();

            $pendingBatches = PayrollBatch::query()
                ->whereNotIn('status', [BatchStatus::Paid, BatchStatus::Posted, BatchStatus::Cancelled])
                ->count();

            $activeLoans = Loan::query()->where('status', LoanStatus::Active)->count();
            $loanOutstanding = Loan::query()
                ->where('status', LoanStatus::Active)
                ->sum('amount_remaining');

            return [
                Stat::make(__('dashboard.stats.total_payroll'), 'ر.ع. '.$totalPayroll)
                    ->color('primary')
                    ->icon('heroicon-o-banknotes'),
                Stat::make(__('dashboard.stats.employees_paid'), $employeesPaid)
                    ->color('success')
                    ->icon('heroicon-o-users'),
                Stat::make(__('dashboard.stats.pending_batches'), $pendingBatches)
                    ->color('warning')
                    ->icon('heroicon-o-calendar-days'),
                Stat::make(__('dashboard.stats.active_loans'), $activeLoans.' / ر.ع. '.number_format((float) $loanOutstanding, 3))
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
