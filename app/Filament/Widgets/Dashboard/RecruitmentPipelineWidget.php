<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\SchemaMemo;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Employee\Models\EmployeeJobPosition;
use Webkul\Recruitment\Models\Applicant;

class RecruitmentPipelineWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 13;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.recruitment');
    }

    protected function getStats(): array
    {
        try {
            if (! SchemaMemo::hasTable('recruitments_applicants')
                || ! class_exists(Applicant::class)) {
                return [
                    Stat::make(__('dashboard.widgets.recruitment'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $openPositions = 0;

            if (SchemaMemo::hasTable('employees_job_positions')
                && class_exists(EmployeeJobPosition::class)) {
                $openPositions = EmployeeJobPosition::query()
                    ->where('is_active', true)
                    ->where('no_of_recruitment', '>', 0)
                    ->count();
            }

            $totalApplicants = Applicant::query()
                ->where('is_active', true)
                ->whereNull('date_closed')
                ->count();

            $hiredThisMonth = Applicant::query()
                ->whereNotNull('date_closed')
                ->whereMonth('date_closed', now()->month)
                ->whereYear('date_closed', now()->year)
                ->count();

            return [
                Stat::make(__('dashboard.stats.open_positions'), $openPositions)
                    ->color($openPositions > 0 ? 'info' : 'gray')
                    ->icon('heroicon-o-briefcase'),
                Stat::make(__('dashboard.stats.total_applicants'), $totalApplicants)
                    ->color($totalApplicants > 0 ? 'info' : 'gray')
                    ->icon('heroicon-o-users'),
                Stat::make(__('dashboard.stats.hired_this_month'), $hiredThisMonth)
                    ->color($hiredThisMonth > 0 ? 'success' : 'gray')
                    ->icon('heroicon-o-user-plus'),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.recruitment'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
