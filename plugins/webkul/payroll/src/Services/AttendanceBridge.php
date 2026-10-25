<?php

namespace Webkul\Payroll\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\Attendance\Services\AttendanceSummary;
use Webkul\Employee\Models\Employee;

/**
 * The only place payroll touches the attendances plugin, which is optional: every other payroll class works
 * with the plain array returned here, so payroll keeps running (with the legacy 22-day month) when the plugin
 * is not installed.
 */
class AttendanceBridge
{
    public function available(): bool
    {
        return class_exists(AttendanceSummary::class) && Schema::hasTable('attendance_days');
    }

    /**
     * The employee's attendance month, or null when the plugin is not installed, the employee resolves no
     * work schedule or the month has no working day (payroll then falls back to the legacy figures).
     *
     * `overtime_hours` holds approved overtime only, keyed by the multiplier formatted with two decimals.
     *
     * @return array{has_schedule: bool, working_days: int, absent_days: int, late_minutes: int, daily_hours: float, overtime_hours: array<string, float>}|null
     */
    public function summary(Employee $employee, int $year, int $month): ?array
    {
        if (! $this->available()) {
            return null;
        }

        $summary = app(AttendanceSummary::class)->forMonth($employee, $year, $month);

        if (! $summary->hasSchedule || $summary->workingDays === 0) {
            return null;
        }

        return [
            'has_schedule'   => $summary->hasSchedule,
            'working_days'   => $summary->workingDays,
            'absent_days'    => $summary->absentDays,
            'late_minutes'   => $summary->lateMinutes,
            'daily_hours'    => $summary->dailyHours,
            'overtime_hours' => $summary->overtimeHours,
        ];
    }
}
