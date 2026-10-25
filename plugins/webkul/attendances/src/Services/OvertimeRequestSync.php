<?php

namespace Webkul\Attendance\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Settings\AttendanceSettings;

class OvertimeRequestSync
{
    public function __construct(
        protected WorkScheduleResolver $resolver,
        protected AttendanceSettings $settings,
    ) {}

    /**
     * Keeps the day's pending overtime request in line with its overtime minutes: created or updated
     * when they reach the minimum, deleted when they do not. A decided request is never touched.
     *
     * The request is read with a row lock, and every write is also conditional on the row still being
     * pending, so a decision that commits between the read and the write cannot be overwritten or removed.
     */
    public function sync(AttendanceDay $day): void
    {
        $dateString = $day->date->toDateString();

        DB::transaction(function () use ($day, $dateString): void {
            $request = OvertimeRequest::query()
                ->where('employee_id', $day->employee_id)
                ->whereDate('date', $dateString)
                ->lockForUpdate()
                ->first();

            if ($request !== null && $request->status !== OvertimeStatus::Pending) {
                return;
            }

            $minutes = (int) $day->overtime_minutes;

            if ($minutes <= 0 || $minutes < $this->settings->minimum_overtime_minutes) {
                if ($request !== null) {
                    $this->pendingRows($request->employee_id, $dateString)->delete();
                }

                return;
            }

            $dayType = $this->dayTypeFor($day);
            $values = [
                'minutes'    => $minutes,
                'day_type'   => $dayType->value,
                'multiplier' => $this->multiplierFor($dayType),
                'status'     => OvertimeStatus::Pending->value,
            ];

            if ($request !== null) {
                $this->pendingRows($day->employee_id, $dateString)->update($values);

                return;
            }

            try {
                OvertimeRequest::query()->create(array_merge(
                    ['employee_id' => $day->employee_id, 'date' => $dateString],
                    $values,
                ));
            } catch (UniqueConstraintViolationException) {
                // Another session created the request first: follow it only while it is still pending.
                $this->pendingRows($day->employee_id, $dateString)->update($values);
            }
        });
    }

    /**
     * The employee's request for the date, only while it is pending.
     *
     * @return Builder<OvertimeRequest>
     */
    protected function pendingRows(int $employeeId, string $dateString): Builder
    {
        return OvertimeRequest::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $dateString)
            ->where('status', OvertimeStatus::Pending->value);
    }

    protected function dayTypeFor(AttendanceDay $day): OvertimeDayType
    {
        $workDay = $this->resolver->forEmployee($day->employee, $day->date);

        return match (true) {
            $workDay->isHoliday => OvertimeDayType::Holiday,
            $workDay->isRestDay => OvertimeDayType::Rest,
            default             => OvertimeDayType::Normal,
        };
    }

    protected function multiplierFor(OvertimeDayType $dayType): float
    {
        return match ($dayType) {
            OvertimeDayType::Normal  => $this->settings->overtime_multiplier_normal,
            OvertimeDayType::Rest    => $this->settings->overtime_multiplier_rest,
            OvertimeDayType::Holiday => $this->settings->overtime_multiplier_holiday,
        };
    }
}
