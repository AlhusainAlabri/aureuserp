<?php

use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Services\AttendanceMonthSummary;
use Webkul\Attendance\Services\AttendanceSummary;
use Webkul\Employee\Models\Employee;
use Webkul\Support\Models\Calendar;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();
});

/**
 * Employee on the default Monday-Friday 08:00-17:00 calendar (480 expected minutes a day).
 *
 * @param  array<string, mixed>  $attributes
 */
function summaryEmployee(array $attributes = []): Employee
{
    $company = AttendanceFixtures::company();

    return AttendanceFixtures::employee($company, AttendanceFixtures::calendar($company), $attributes);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function summaryDay(Employee $employee, string $date, DayStatus $status, array $attributes = []): AttendanceDay
{
    return AttendanceDay::query()->create(array_merge([
        'employee_id'      => $employee->id,
        'date'             => $date,
        'status'           => $status,
        'expected_minutes' => 480,
    ], $attributes));
}

function summaryOvertime(Employee $employee, string $date, int $minutes, float $multiplier, OvertimeStatus $status): OvertimeRequest
{
    return OvertimeRequest::query()->create([
        'employee_id' => $employee->id,
        'date'        => $date,
        'minutes'     => $minutes,
        'day_type'    => OvertimeDayType::Normal,
        'multiplier'  => $multiplier,
        'status'      => $status,
    ]);
}

// November 2026 starts on a Sunday: 21 Monday-Friday days.

it('AttendanceSummaryTest: working days follow the schedule and leave holidays out', function (): void {
    $employee = summaryEmployee();
    AttendanceFixtures::holiday('2026-11-10', '2026-11-11', $employee->calendar_id);

    $summary = app(AttendanceSummary::class)->forMonth($employee, 2026, 11);

    expect($summary)->toBeInstanceOf(AttendanceMonthSummary::class)
        ->and($summary->hasSchedule)->toBeTrue()
        ->and($summary->workingDays)->toBe(19)
        ->and($summary->dailyHours)->toBe(8.0);
});

it('AttendanceSummaryTest: working days are counted from the schedule, not from stored rows', function (): void {
    $employee = summaryEmployee();

    expect(app(AttendanceSummary::class)->forMonth($employee, 2026, 11)->workingDays)->toBe(21)
        ->and(AttendanceDay::query()->where('employee_id', $employee->id)->count())->toBe(0);
});

it('AttendanceSummaryTest: absent days and late minutes are summed over the month only', function (): void {
    $employee = summaryEmployee();
    $other = summaryEmployee();

    summaryDay($employee, '2026-11-02', DayStatus::Absent);
    summaryDay($employee, '2026-11-03', DayStatus::Absent);
    summaryDay($employee, '2026-11-04', DayStatus::Present, ['late_minutes' => 25]);
    summaryDay($employee, '2026-11-05', DayStatus::Present, ['late_minutes' => 15]);
    summaryDay($employee, '2026-11-30', DayStatus::Absent, ['late_minutes' => 0]);
    // Outside the month.
    summaryDay($employee, '2026-10-30', DayStatus::Absent, ['late_minutes' => 99]);
    summaryDay($employee, '2026-12-01', DayStatus::Absent, ['late_minutes' => 99]);
    // Another employee.
    summaryDay($other, '2026-11-02', DayStatus::Absent, ['late_minutes' => 50]);

    $summary = app(AttendanceSummary::class)->forMonth($employee, 2026, 11);

    expect($summary->absentDays)->toBe(3)
        ->and($summary->lateMinutes)->toBe(40);
});

it('AttendanceSummaryTest: overtime counts approved requests only, grouped by their stored multiplier', function (): void {
    $employee = summaryEmployee();
    $other = summaryEmployee();

    summaryOvertime($employee, '2026-11-02', 90, 1.25, OvertimeStatus::Approved);
    summaryOvertime($employee, '2026-11-03', 60, 1.25, OvertimeStatus::Approved);
    summaryOvertime($employee, '2026-11-07', 120, 1.5, OvertimeStatus::Approved);
    summaryOvertime($employee, '2026-11-04', 300, 1.25, OvertimeStatus::Pending);
    summaryOvertime($employee, '2026-11-05', 300, 1.5, OvertimeStatus::Rejected);
    // Outside the month and another employee.
    summaryOvertime($employee, '2026-12-01', 600, 1.25, OvertimeStatus::Approved);
    summaryOvertime($other, '2026-11-02', 600, 1.25, OvertimeStatus::Approved);

    $summary = app(AttendanceSummary::class)->forMonth($employee, 2026, 11);

    expect($summary->overtimeHours)->toBe(['1.25' => 2.5, '1.50' => 2.0]);
});

it('AttendanceSummaryTest: the approved request is authoritative even when the day lost its overtime', function (): void {
    $employee = summaryEmployee();

    summaryDay($employee, '2026-11-02', DayStatus::Present, ['overtime_minutes' => 0]);
    summaryOvertime($employee, '2026-11-02', 45, 1.25, OvertimeStatus::Approved);

    $summary = app(AttendanceSummary::class)->forMonth($employee, 2026, 11);

    expect($summary->overtimeHours)->toBe(['1.25' => 0.75]);
});

it('AttendanceSummaryTest: a month without overtime has an empty overtime list', function (): void {
    $summary = app(AttendanceSummary::class)->forMonth(summaryEmployee(), 2026, 11);

    expect($summary->overtimeHours)->toBe([])
        ->and($summary->absentDays)->toBe(0)
        ->and($summary->lateMinutes)->toBe(0);
});

it('AttendanceSummaryTest: an employee without a calendar has no schedule', function (): void {
    Calendar::query()->update(['is_active' => false]);

    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    $summary = app(AttendanceSummary::class)->forMonth($employee, 2026, 11);

    expect($summary->hasSchedule)->toBeFalse()
        ->and($summary->workingDays)->toBe(0)
        ->and($summary->dailyHours)->toBe(0.0);
});

it('AttendanceSummaryTest: daily hours average the expected minutes over the working days', function (): void {
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company, [
        'monday'  => [['morning', '8', '12']],
        'tuesday' => [['morning', '8', '12'], ['lunch', '12', '13'], ['afternoon', '13', '17']],
    ]);
    $employee = AttendanceFixtures::employee($company, $calendar);

    // November 2026: 5 Mondays (2, 9, 16, 23, 30) of 240 minutes and 4 Tuesdays of 480 minutes.
    $summary = app(AttendanceSummary::class)->forMonth($employee, 2026, 11);

    expect($summary->workingDays)->toBe(9)
        ->and($summary->dailyHours)->toBe(round((5 * 240 + 4 * 480) / 9 / 60, 2));
});

it('AttendanceSummaryTest: the summary exposes exactly the documented read-only properties', function (): void {
    $properties = array_map(
        fn (ReflectionProperty $property): string => $property->getName(),
        (new ReflectionClass(AttendanceMonthSummary::class))->getProperties(),
    );

    sort($properties);

    expect((new ReflectionClass(AttendanceMonthSummary::class))->isReadOnly())->toBeTrue()
        ->and($properties)->toBe(['absentDays', 'dailyHours', 'hasSchedule', 'lateMinutes', 'overtimeHours', 'workingDays']);
});
