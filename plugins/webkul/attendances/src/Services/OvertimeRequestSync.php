<?php

namespace Webkul\Attendance\Services;

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
     */
    public function sync(AttendanceDay $day): void
    {
        $dateString = $day->date->toDateString();

        $request = OvertimeRequest::query()
            ->where('employee_id', $day->employee_id)
            ->whereDate('date', $dateString)
            ->first();

        if ($request !== null && $request->status !== OvertimeStatus::Pending) {
            return;
        }

        $minutes = (int) $day->overtime_minutes;

        if ($minutes <= 0 || $minutes < $this->settings->minimum_overtime_minutes) {
            $request?->delete();

            return;
        }

        $dayType = $this->dayTypeFor($day);

        OvertimeRequest::query()->updateOrCreate(
            ['employee_id' => $day->employee_id, 'date' => $dateString],
            [
                'minutes'    => $minutes,
                'day_type'   => $dayType,
                'multiplier' => $this->multiplierFor($dayType),
                'status'     => OvertimeStatus::Pending,
            ],
        );
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
