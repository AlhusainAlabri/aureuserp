<?php

namespace Webkul\Attendance\Services;

use Illuminate\Support\Carbon;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Employee\Models\Employee;

class AttendanceSummary
{
    public function __construct(protected WorkScheduleResolver $resolver) {}

    /**
     * The employee's month in local dates (the `date` columns carry no time part): working days come
     * from the schedule, absences and lateness from the stored days, and overtime from the approved
     * requests (a decided request outlives the day's own overtime minutes, so it is authoritative).
     */
    public function forMonth(Employee $employee, int $year, int $month): AttendanceMonthSummary
    {
        $first = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC');
        $from = $first->toDateString();
        $to = $first->copy()->endOfMonth()->toDateString();

        $workingDays = 0;
        $expectedMinutes = 0;
        $hasSchedule = false;

        foreach ($this->resolver->forEmployeeMonth($employee, $year, $month) as $workDay) {
            $hasSchedule = $workDay->hasSchedule;

            if ($workDay->isWorkingDay) {
                $workingDays++;
                $expectedMinutes += $workDay->expectedMinutes;
            }
        }

        $days = AttendanceDay::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$from, $to]);

        return new AttendanceMonthSummary(
            hasSchedule: $hasSchedule,
            workingDays: $workingDays,
            absentDays: (clone $days)->where('status', DayStatus::Absent->value)->count(),
            lateMinutes: (int) (clone $days)->sum('late_minutes'),
            dailyHours: $workingDays === 0 ? 0.0 : round($expectedMinutes / $workingDays / 60, 2),
            overtimeHours: $this->approvedOvertimeHours($employee, $from, $to),
        );
    }

    /**
     * @return array<string, float>
     */
    protected function approvedOvertimeHours(Employee $employee, string $from, string $to): array
    {
        $minutesByMultiplier = [];

        OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', OvertimeStatus::Approved->value)
            ->whereBetween('date', [$from, $to])
            ->get(['minutes', 'multiplier'])
            ->each(function (OvertimeRequest $request) use (&$minutesByMultiplier): void {
                $key = number_format((float) $request->multiplier, 2, '.', '');

                $minutesByMultiplier[$key] = ($minutesByMultiplier[$key] ?? 0) + $request->minutes;
            });

        ksort($minutesByMultiplier, SORT_NUMERIC);

        return array_map(
            fn (int $minutes): float => round($minutes / 60, 2),
            $minutesByMultiplier,
        );
    }
}
