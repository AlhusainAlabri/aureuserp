<?php

namespace Webkul\Attendance\Services;

/**
 * The expected work of one employee on one local date, read from the work calendar.
 *
 * Minutes are counted from local midnight. On a holiday or a rest day `isWorkingDay` is false and
 * `expectedMinutes` is 0, while the shift bounds and lunch still describe the weekday rows (if any).
 */
final readonly class WorkDay
{
    /**
     * @param  list<array{from: int, to: int}>  $lunch
     */
    public function __construct(
        public bool $isWorkingDay,
        public bool $hasSchedule,
        public ?int $startMinute,
        public ?int $endMinute,
        public int $expectedMinutes,
        public array $lunch,
        public ?string $holidayName,
        public bool $isHoliday,
        public bool $isRestDay,
    ) {}
}
