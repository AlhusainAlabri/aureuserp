<?php

namespace Webkul\Attendance\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
     *
     * Stored days are not recalculated when a holiday is declared or a leave is approved after the
     * nightly close, so a stored row is trusted only on a date that is still a working day: absences
     * on approved-leave dates and absences or lateness on holidays and rest days are left out (payroll
     * already counts unpaid leave on its own, and the holiday leaves the working days).
     */
    public function forMonth(Employee $employee, int $year, int $month): AttendanceMonthSummary
    {
        $first = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC');
        $from = $first->toDateString();
        $to = $first->copy()->endOfMonth()->toDateString();

        $workingDays = 0;
        $expectedMinutes = 0;
        $hasSchedule = false;

        $workingDates = [];

        foreach ($this->resolver->forEmployeeMonth($employee, $year, $month) as $date => $workDay) {
            $hasSchedule = $workDay->hasSchedule;

            if ($workDay->isWorkingDay) {
                $workingDays++;
                $expectedMinutes += $workDay->expectedMinutes;
                $workingDates[$date] = true;
            }
        }

        $leaveDates = $this->approvedLeaveDates($employee, $from, $to);
        $absentDays = 0;
        $lateMinutes = 0;

        AttendanceDay::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$from, $to])
            ->get(['date', 'status', 'late_minutes'])
            ->each(function (AttendanceDay $day) use ($workingDates, $leaveDates, &$absentDays, &$lateMinutes): void {
                $date = $day->date->toDateString();

                if (! isset($workingDates[$date])) {
                    return;
                }

                $lateMinutes += (int) $day->late_minutes;

                if ($day->status === DayStatus::Absent && ! isset($leaveDates[$date])) {
                    $absentDays++;
                }
            });

        return new AttendanceMonthSummary(
            hasSchedule: $hasSchedule,
            workingDays: $workingDays,
            absentDays: $absentDays,
            lateMinutes: $lateMinutes,
            dailyHours: $workingDays === 0 ? 0.0 : round($expectedMinutes / $workingDays / 60, 2),
            overtimeHours: $this->approvedOvertimeHours($employee, $from, $to),
        );
    }

    /**
     * Working days of the month that have no stored attendance day yet: the nightly close has not
     * reached them (still in the future, or the job was missed), so absences and lateness for them are
     * not known and payroll built now would pay them in full.
     */
    public function openDays(Employee $employee, int $year, int $month): int
    {
        $first = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC');

        $workingDates = collect($this->resolver->forEmployeeMonth($employee, $year, $month))
            ->filter(fn (WorkDay $workDay): bool => $workDay->isWorkingDay)
            ->keys();

        if ($workingDates->isEmpty()) {
            return 0;
        }

        $stored = AttendanceDay::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$first->toDateString(), $first->copy()->endOfMonth()->toDateString()])
            ->get(['date'])
            ->map(fn (AttendanceDay $day): string => $day->date->toDateString())
            ->all();

        return $workingDates->diff($stored)->count();
    }

    /**
     * Local dates between `$from` and `$to` covered by the employee's approved Time Off (one query for
     * the month; the same overlap rule as `WorkScheduleResolver::approvedLeaveId`).
     *
     * @return array<string, true>
     */
    protected function approvedLeaveDates(Employee $employee, string $from, string $to): array
    {
        if (! Schema::hasTable('time_off_leaves')) {
            return [];
        }

        $dates = [];

        DB::table('time_off_leaves')
            ->where('employee_id', $employee->id)
            ->whereIn('state', ['validate_one', 'validate_two'])
            ->where('date_from', '<=', $to.' 23:59:59')
            ->where('date_to', '>=', $from.' 00:00:00')
            ->get(['date_from', 'date_to'])
            ->each(function (object $leave) use ($from, $to, &$dates): void {
                $start = max($from, substr((string) $leave->date_from, 0, 10));
                $end = min($to, substr((string) $leave->date_to, 0, 10));

                for ($day = Carbon::parse($start, 'UTC'); $day->toDateString() <= $end; $day->addDay()) {
                    $dates[$day->toDateString()] = true;
                }
            });

        return $dates;
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
