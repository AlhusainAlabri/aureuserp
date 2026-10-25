<?php

namespace Webkul\Attendance\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

/**
 * HR's manual entry for a day: the figures are taken as typed, the row becomes a manual override (so no
 * later punch recomputes it) and the day's pending overtime request follows the entered overtime. The
 * original punches are never touched.
 *
 * Times arrive as wall-clock strings in the employee's own zone and are stored as UTC.
 *
 * @phpstan-type DayInput array{employee_id?: int|string, date?: string, status: string|\Webkul\Attendance\Enums\DayStatus, first_in?: ?string, last_out?: ?string, late_minutes?: int|string|null, early_leave_minutes?: int|string|null, overtime_minutes?: int|string|null, override_note: string}
 */
class ManualDayEntry
{
    public function __construct(
        protected WorkScheduleResolver $resolver,
        protected OvertimeRequestSync $overtimeSync,
    ) {}

    /**
     * @param  DayInput  $data
     *
     * @throws ValidationException when the employee is out of scope or the day already has a row
     */
    public function create(array $data, User $by): AttendanceDay
    {
        $employee = Employee::query()->withTrashed()->find($data['employee_id'] ?? null);

        if ($employee === null || ! AttendanceScope::includes($by, $employee->id)) {
            throw ValidationException::withMessages([
                'employee_id' => __('attendances::attendances.day.errors.employee_not_allowed'),
            ]);
        }

        $date = Carbon::createFromFormat('Y-m-d', substr((string) $data['date'], 0, 10), 'UTC')->startOfDay();

        if (AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', $date->toDateString())->exists()) {
            throw $this->duplicate();
        }

        try {
            return DB::transaction(function () use ($data, $by, $employee, $date): AttendanceDay {
                $day = new AttendanceDay(['employee_id' => $employee->id, 'date' => $date->toDateString()]);

                return $this->persist($day, $employee, $date, $data, $by);
            });
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate();
        }
    }

    /**
     * Employee and date identify the row and are not editable.
     *
     * @param  DayInput  $data
     */
    public function update(AttendanceDay $day, array $data, User $by): AttendanceDay
    {
        $employee = $day->employee;

        if (! AttendanceScope::includes($by, $employee->id)) {
            throw ValidationException::withMessages([
                'employee_id' => __('attendances::attendances.day.errors.employee_not_allowed'),
            ]);
        }

        return DB::transaction(fn (): AttendanceDay => $this->persist($day, $employee, $day->date, $data, $by));
    }

    /**
     * @param  DayInput  $data
     */
    protected function persist(AttendanceDay $day, Employee $employee, CarbonInterface $date, array $data, User $by): AttendanceDay
    {
        $zone = $employee->time_zone ?: config('app.timezone');
        $firstIn = $this->toUtc($data['first_in'] ?? null, $zone);
        $lastOut = $this->toUtc($data['last_out'] ?? null, $zone);

        $workedMinutes = $firstIn !== null && $lastOut !== null
            ? max(0, intdiv($lastOut->getTimestamp() - $firstIn->getTimestamp(), 60))
            : 0;

        $day->fill([
            'status'              => $data['status'],
            'first_in'            => $firstIn,
            'last_out'            => $lastOut,
            'worked_minutes'      => $workedMinutes,
            'expected_minutes'    => $this->resolver->forEmployee($employee, $date)->expectedMinutes,
            'late_minutes'        => (int) ($data['late_minutes'] ?? 0),
            'early_leave_minutes' => (int) ($data['early_leave_minutes'] ?? 0),
            'overtime_minutes'    => (int) ($data['overtime_minutes'] ?? 0),
            'is_manual_override'  => true,
            'override_note'       => $data['override_note'],
            'edited_by'           => $by->getKey(),
        ]);
        $day->save();

        $this->overtimeSync->sync($day->load('employee'));

        return $day;
    }

    protected function toUtc(?string $local, string $zone): ?Carbon
    {
        return filled($local) ? Carbon::parse($local, $zone)->utc() : null;
    }

    protected function duplicate(): ValidationException
    {
        return ValidationException::withMessages([
            'date' => __('attendances::attendances.day.errors.duplicate'),
        ]);
    }
}
