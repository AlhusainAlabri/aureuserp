<?php

namespace Webkul\Employee\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Employee\Models\EmployeeSubmission;

class SubmissionsOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                __('employees::filament/widgets/submissions-overview.stats.open'),
                EmployeeSubmission::open()->count()
            )
                ->icon('heroicon-o-inbox')
                ->color('warning'),

            Stat::make(
                __('employees::filament/widgets/submissions-overview.stats.under-review'),
                EmployeeSubmission::underReview()->count()
            )
                ->icon('heroicon-o-eye')
                ->color('info'),

            Stat::make(
                __('employees::filament/widgets/submissions-overview.stats.resolved-this-month'),
                EmployeeSubmission::where('status', 'resolved')->thisMonth()->count()
            )
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(
                __('employees::filament/widgets/submissions-overview.stats.total-this-month'),
                EmployeeSubmission::thisMonth()->count()
            )
                ->icon('heroicon-o-document-text')
                ->color('gray'),
        ];
    }
}
