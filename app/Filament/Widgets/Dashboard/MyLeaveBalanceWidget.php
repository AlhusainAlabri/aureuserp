<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\SchemaMemo;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Employee\Models\Employee;
use Webkul\TimeOff\Enums\State;
use Webkul\TimeOff\Models\Leave;
use Webkul\TimeOff\Models\LeaveAllocation;
use Webkul\TimeOff\Models\LeaveType;

class MyLeaveBalanceWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 20;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.my_leave');
    }

    protected function getStats(): array
    {
        try {
            if (! SchemaMemo::hasTable('time_off_leaves')
                || ! SchemaMemo::hasTable('time_off_leave_allocations')
                || ! class_exists(Leave::class)) {
                return [
                    Stat::make(__('dashboard.widgets.my_leave'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $employee = Employee::query()->where('user_id', auth()->id())->first();

            if (! $employee) {
                return [
                    Stat::make(__('dashboard.widgets.my_leave'), __('dashboard.data_unavailable'))
                        ->color('gray'),
                ];
            }

            $endOfYear = Carbon::now()->endOfYear();

            $annualDays = $this->calculateAvailableDays(
                $employee->id,
                $this->findLeaveTypeId(['paid', 'annual']),
                $endOfYear,
            );

            $sickDays = $this->calculateAvailableDays(
                $employee->id,
                $this->findLeaveTypeId(['sick']),
                $endOfYear,
            );

            return [
                Stat::make(__('dashboard.stats.annual_leave'), __('dashboard.stats.days_remaining', ['days' => $annualDays]))
                    ->color('info')
                    ->icon('heroicon-o-sun'),
                Stat::make(__('dashboard.stats.sick_leave'), __('dashboard.stats.days_remaining', ['days' => $sickDays]))
                    ->color('info')
                    ->icon('heroicon-o-heart'),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.my_leave'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }

    private function findLeaveTypeId(array $keywords): ?int
    {
        if (! class_exists(LeaveType::class) || ! SchemaMemo::hasTable('time_off_leave_types')) {
            return null;
        }

        return LeaveType::query()
            ->where('show_on_dashboard', '!=', 0)
            ->where(function ($query) use ($keywords): void {
                foreach ($keywords as $keyword) {
                    $query->orWhere('name', 'like', '%'.$keyword.'%');
                }
            })
            ->value('id');
    }

    private function calculateAvailableDays(int $employeeId, ?int $leaveTypeId, Carbon $endDate): string
    {
        if ($leaveTypeId === null) {
            return '—';
        }

        $totalAllocated = LeaveAllocation::query()
            ->where('employee_id', $employeeId)
            ->where('holiday_status_id', $leaveTypeId)
            ->where('state', State::VALIDATE_TWO->value)
            ->where(function ($query) use ($endDate): void {
                $query->where('date_to', '<=', $endDate)
                    ->orWhereNull('date_to');
            })
            ->sum('number_of_days');

        $totalTaken = Leave::query()
            ->where('employee_id', $employeeId)
            ->where('holiday_status_id', $leaveTypeId)
            ->where(function ($query) use ($endDate): void {
                $query->where('request_date_to', '<=', $endDate)
                    ->orWhereNull('request_date_to');
            })
            ->where('state', '!=', State::REFUSE->value)
            ->sum('number_of_days');

        return number_format(max(0, (float) $totalAllocated - (float) $totalTaken), 1);
    }
}
