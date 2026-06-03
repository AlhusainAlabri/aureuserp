<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class RecruitmentPipelineWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 13;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.recruitment');
    }

    protected function getStats(): array
    {
        try {
            if (! Schema::hasTable('job_applications')) {
                return [
                    Stat::make(__('dashboard.widgets.recruitment'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            return [
                Stat::make(__('dashboard.stats.open_positions'), 0)
                    ->color('info')
                    ->icon('heroicon-o-briefcase'),
                Stat::make(__('dashboard.stats.total_applicants'), 0)
                    ->color('info')
                    ->icon('heroicon-o-users'),
                Stat::make(__('dashboard.stats.hired_this_month'), 0)
                    ->color('success')
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
