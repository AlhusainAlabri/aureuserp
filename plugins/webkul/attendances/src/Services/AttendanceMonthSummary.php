<?php

namespace Webkul\Attendance\Services;

/**
 * One employee's attendance figures for a calendar month.
 *
 * `overtimeHours` holds the approved overtime in hours, keyed by the request's stored multiplier
 * formatted with two decimals (`'1.25'`).
 */
final readonly class AttendanceMonthSummary
{
    /**
     * @param  array<string, float>  $overtimeHours
     */
    public function __construct(
        public bool $hasSchedule,
        public int $workingDays,
        public int $absentDays,
        public int $lateMinutes,
        public float $dailyHours,
        public array $overtimeHours,
    ) {}
}
