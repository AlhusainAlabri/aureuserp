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
     * Returns without saving anything — an existing row as it is, otherwise a new unsaved row — when
     * the stored row is a manual override, or the employee is inactive or left before the date.
     *
     * A day without punches, leave or holiday that is not a rest day has no basis of its own; see
     * withoutBasis() for how it is returned and how a stale stored row is reset.
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
                return $this->withoutBasis($employee, $workDay, $dateString, $zone, $existing);
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
     * Lateness, early leave and overtime the day's rules would charge for a check-in and check-out typed
     * by hand, so the manual entry form can offer them as starting values. Nothing is stored.
     *
     * @param  string  $localIn  check-in, local to the employee's time zone
     * @param  string  $localOut  check-out, local to the employee's time zone
     * @return array{late_minutes: int, early_leave_minutes: int, overtime_minutes: int}
     */
    public function suggest(Employee $employee, CarbonInterface $date, string $localIn, string $localOut): array
    {
        $zone = $employee->time_zone ?: config('app.timezone');
        $in = Carbon::parse($localIn, $zone)->utc();
        $out = Carbon::parse($localOut, $zone)->utc();

        if ($out->lessThanOrEqualTo($in)) {
            return ['late_minutes' => 0, 'early_leave_minutes' => 0, 'overtime_minutes' => 0];
        }

        $punches = collect([$in, $out])->map(
            fn (Carbon $instant): AttendancePunch => (new AttendancePunch)->forceFill(['punched_at' => $instant]),
        );

        $leaveId = $this->resolver->approvedLeaveId($employee, $date);
        $values = $this->fromPunches($employee, $this->resolver->forEmployee($employee, $date), $punches, $zone, $leaveId);

        return [
            'late_minutes'        => (int) $values['late_minutes'],
            'early_leave_minutes' => (int) $values['early_leave_minutes'],
            'overtime_minutes'    => (int) $values['overtime_minutes'],
        ];
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

        // A lone punch at or after the shift midpoint is a check-out with a missing check-in, not lateness.
        $isMissingCheckIn = $outMinute === null
            && $timeRules
            && $inMinute * 2 >= $workDay->startMinute + $workDay->endMinute;

        $late = $timeRules && ! $isMissingCheckIn
            ? min(
                $workDay->expectedMinutes,
                max(0, $inMinute - ($workDay->startMinute + $this->settings->grace_minutes)),
            )
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
     * A day without punches, leave or holiday that is not a rest day. Absences are written by the nightly
     * close, so nothing new is stored: the result is a new unsaved row (status `absent` on a scheduled
     * working day, null otherwise). A stored row left from an earlier basis (cancelled leave, deleted
     * holiday, removed punches) never keeps that stale data:
     * - on a scheduled working day that is already closed (before today in the employee's zone), or when
     *   the row is already `absent`, it is reset in place to an `absent` day with zero minutes;
     * - otherwise (an open day, or no schedule) it is deleted and the nightly close decides.
     * Either way the pending overtime request is synced away; a decided one stays.
     */
    protected function withoutBasis(Employee $employee, WorkDay $workDay, string $dateString, string $zone, ?AttendanceDay $existing): AttendanceDay
    {
        $unsaved = new AttendanceDay([
            'employee_id'      => $employee->id,
            'date'             => $dateString,
            'expected_minutes' => $workDay->expectedMinutes,
            'status'           => $workDay->isWorkingDay ? DayStatus::Absent : null,
        ]);
        $unsaved->setRelation('employee', $employee);

        if ($existing === null) {
            return $unsaved;
        }

        $isClosedDay = $dateString < Carbon::now($zone)->toDateString();

        if ($workDay->isWorkingDay && ($isClosedDay || $existing->status === DayStatus::Absent)) {
            return $this->store($employee, $dateString, [
                'first_in'            => null,
                'last_out'            => null,
                'worked_minutes'      => 0,
                'expected_minutes'    => $workDay->expectedMinutes,
                'late_minutes'        => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes'    => 0,
                'status'              => DayStatus::Absent,
                'leave_id'            => null,
            ]);
        }

        $existing->delete();

        $this->overtimeRequestSync->sync($unsaved);

        return $unsaved;
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
