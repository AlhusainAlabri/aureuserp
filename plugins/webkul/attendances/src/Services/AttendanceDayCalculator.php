<?php

namespace Webkul\Attendance\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Employee\Models\Employee;

class AttendanceDayCalculator
{
    public function __construct(
        protected WorkScheduleResolver $resolver,
        protected OvertimeRequestSync $overtimeRequestSync,
        protected AttendanceSettings $settings,
    ) {}

    /**
     * Computes the employee's attendance for the local date (only `$date->toDateString()` is used) from
     * that day's punches and work calendar, stores it and syncs the pending overtime request.
     *
     * Returns without saving anything — an existing row as it is, otherwise a new unsaved row — when:
     * the stored row is a manual override; the employee is inactive or left before the date; or it is a
     * day without punches, leave or holiday that is not a rest day (absences are written by the
     * nightly close, which receives such a row with the `absent` status when the day was a working day).
     */
    public function calculate(Employee $employee, CarbonInterface $date): AttendanceDay
    {
        $dateString = $date->toDateString();

        $existing = AttendanceDay::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $dateString)
            ->first();

        if ($existing?->is_manual_override) {
            return $existing;
        }

        if (! $this->isEmployedOn($employee, $dateString)) {
            return $existing ?? new AttendanceDay(['employee_id' => $employee->id, 'date' => $dateString]);
        }

        $zone = $employee->time_zone ?: config('app.timezone');
        $workDay = $this->resolver->forEmployee($employee, $date);
        $punches = $this->punchesFor($employee, $dateString, $zone);
        $leaveId = $workDay->isHoliday ? null : $this->resolver->approvedLeaveId($employee, $date);

        if ($punches->isEmpty()) {
            $status = match (true) {
                $workDay->isHoliday => DayStatus::Holiday,
                $leaveId !== null   => DayStatus::Leave,
                $workDay->isRestDay => DayStatus::Rest,
                default             => null,
            };

            if ($status === null) {
                return $existing ?? new AttendanceDay([
                    'employee_id'      => $employee->id,
                    'date'             => $dateString,
                    'expected_minutes' => $workDay->expectedMinutes,
                    'status'           => $workDay->isWorkingDay ? DayStatus::Absent : null,
                ]);
            }

            return $this->store($employee, $dateString, [
                'first_in'            => null,
                'last_out'            => null,
                'worked_minutes'      => 0,
                'expected_minutes'    => $workDay->expectedMinutes,
                'late_minutes'        => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes'    => 0,
                'status'              => $status,
                'leave_id'            => $leaveId,
            ]);
        }

        return $this->store($employee, $dateString, $this->fromPunches($employee, $workDay, $punches, $zone, $leaveId));
    }

    /**
     * @param  Collection<int, AttendancePunch>  $punches  sorted by instant
     * @return array<string, mixed>
     */
    protected function fromPunches(Employee $employee, WorkDay $workDay, Collection $punches, string $zone, ?int $leaveId): array
    {
        $firstIn = $punches->first()->punched_at;
        $lastPunch = $punches->last()->punched_at;

        $inMinute = $this->minuteOfDay($firstIn, $zone);
        $lastMinute = $this->minuteOfDay($lastPunch, $zone);

        // Punches within the same minute are one tap: there is no distinct check-out.
        $outMinute = $lastMinute > $inMinute ? $lastMinute : null;

        $worked = $outMinute === null ? 0 : max(0, $outMinute - $inMinute - $this->lunchOverlap($workDay, $inMinute, $outMinute));

        $isFlexible = (bool) ($employee->is_flexible || $employee->is_fully_flexible);

        // Lateness and early leave apply to scheduled working days only; on a leave day the leave may
        // cover part of the shift, so they are not charged either.
        $timeRules = $workDay->hasSchedule && $workDay->isWorkingDay && ! $isFlexible && $leaveId === null;

        $late = $timeRules
            ? max(0, $inMinute - ($workDay->startMinute + $this->settings->grace_minutes))
            : 0;

        $earlyLeave = $timeRules && $outMinute !== null
            ? max(0, $workDay->endMinute - $outMinute)
            : 0;

        return [
            'first_in'            => $firstIn,
            'last_out'            => $outMinute === null ? null : $lastPunch,
            'worked_minutes'      => $worked,
            'expected_minutes'    => $workDay->expectedMinutes,
            'late_minutes'        => $late,
            'early_leave_minutes' => $earlyLeave,
            'overtime_minutes'    => $this->overtimeMinutes($workDay, $isFlexible, $worked, $outMinute),
            'status'              => DayStatus::Present,
            'leave_id'            => $leaveId,
        ];
    }

    protected function overtimeMinutes(WorkDay $workDay, bool $isFlexible, int $worked, ?int $outMinute): int
    {
        if (! $workDay->hasSchedule || $outMinute === null) {
            return 0;
        }

        $overtime = match (true) {
            $workDay->isHoliday || $workDay->isRestDay => $worked,
            $isFlexible                                => max(0, $worked - $workDay->expectedMinutes),
            default                                    => max(0, $outMinute - $workDay->endMinute),
        };

        return $overtime >= $this->settings->minimum_overtime_minutes ? $overtime : 0;
    }

    protected function lunchOverlap(WorkDay $workDay, int $inMinute, int $outMinute): int
    {
        $overlap = 0;

        foreach ($workDay->lunch as $break) {
            $overlap += max(0, min($outMinute, $break['to']) - max($inMinute, $break['from']));
        }

        return $overlap;
    }

    /**
     * Punches whose instant falls on the local date in the employee's zone, earliest first.
     *
     * @return Collection<int, AttendancePunch>
     */
    protected function punchesFor(Employee $employee, string $dateString, string $zone): Collection
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $dateString.' 00:00:00', $zone);
        $end = $start->copy()->addDay();

        return AttendancePunch::query()
            ->where('employee_id', $employee->id)
            ->where('punched_at', '>=', $start->copy()->utc()->format('Y-m-d H:i:s'))
            ->where('punched_at', '<', $end->copy()->utc()->format('Y-m-d H:i:s'))
            ->orderBy('punched_at')
            ->orderBy('id')
            ->get();
    }

    protected function minuteOfDay(CarbonInterface $instant, string $zone): int
    {
        $local = $instant->copy()->setTimezone($zone);

        return $local->hour * 60 + $local->minute;
    }

    protected function isEmployedOn(Employee $employee, string $dateString): bool
    {
        if (! $employee->is_active) {
            return false;
        }

        if (blank($employee->departure_date)) {
            return true;
        }

        return Carbon::parse($employee->departure_date)->toDateString() >= $dateString;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function store(Employee $employee, string $dateString, array $values): AttendanceDay
    {
        $day = AttendanceDay::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $dateString],
            $values,
        );

        $day->setRelation('employee', $employee);

        $this->overtimeRequestSync->sync($day);

        return $day;
    }
}
