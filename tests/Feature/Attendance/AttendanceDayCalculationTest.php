<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Services\AttendanceDayCalculator;
use Webkul\Attendance\Services\PunchRecorder;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Employee\Models\Employee;
use Webkul\Support\Models\Calendar;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();

    $settings = app(AttendanceSettings::class);
    $settings->grace_minutes = 10;
    $settings->minimum_overtime_minutes = 30;
    $settings->overtime_multiplier_normal = 1.25;
    $settings->overtime_multiplier_rest = 1.5;
    $settings->overtime_multiplier_holiday = 1.5;
    $settings->save();
});

/**
 * Employee on the default Monday-Friday 08:00-17:00 calendar with a 12:00-13:00 lunch.
 *
 * @param  array<string, mixed>  $attributes
 */
function attendanceDayEmployee(array $attributes = []): Employee
{
    $company = AttendanceFixtures::company();

    return AttendanceFixtures::employee($company, AttendanceFixtures::calendar($company), $attributes);
}

/**
 * Inserts the local punches directly and recalculates the day once.
 */
function attendanceDayFor(Employee $employee, string $date, array $times): AttendanceDay
{
    foreach ($times as $time) {
        AttendanceFixtures::punch($employee, $date.' '.$time);
    }

    return app(AttendanceDayCalculator::class)->calculate($employee, Carbon::parse($date, 'Asia/Muscat'));
}

function attendanceStoredDay(Employee $employee, string $date): ?AttendanceDay
{
    return AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', $date)->first();
}

function attendanceOvertimeFor(Employee $employee, string $date): ?OvertimeRequest
{
    return OvertimeRequest::query()->where('employee_id', $employee->id)->whereDate('date', $date)->first();
}

// 2026-11-02 is a Monday, 2026-11-07 a Saturday.

it('AttendanceDayCalculationTest: an on-time day is present with 480 worked minutes', function (): void {
    $employee = attendanceDayEmployee();

    $day = attendanceDayFor($employee, '2026-11-02', ['08:00:00', '17:00:00']);

    expect($day->exists)->toBeTrue()
        ->and($day->status)->toBe(DayStatus::Present)
        ->and($day->date->toDateString())->toBe('2026-11-02')
        ->and($day->first_in->format('Y-m-d H:i:s'))->toBe('2026-11-02 04:00:00')
        ->and($day->last_out->format('Y-m-d H:i:s'))->toBe('2026-11-02 13:00:00')
        ->and($day->worked_minutes)->toBe(480)
        ->and($day->expected_minutes)->toBe(480)
        ->and($day->late_minutes)->toBe(0)
        ->and($day->early_leave_minutes)->toBe(0)
        ->and($day->overtime_minutes)->toBe(0)
        ->and(attendanceOvertimeFor($employee, '2026-11-02'))->toBeNull()
        ->and(attendanceStoredDay($employee, '2026-11-02')?->id)->toBe($day->id);
});

it('AttendanceDayCalculationTest: arriving at 08:25 is 15 minutes late after the grace period', function (): void {
    $day = attendanceDayFor(attendanceDayEmployee(), '2026-11-02', ['08:25:00', '17:00:00']);

    expect($day->late_minutes)->toBe(15)
        ->and($day->worked_minutes)->toBe(455);
});

it('AttendanceDayCalculationTest: arriving within the grace period is not late', function (): void {
    expect(attendanceDayFor(attendanceDayEmployee(), '2026-11-02', ['08:10:00', '17:00:00'])->late_minutes)->toBe(0);
});

it('AttendanceDayCalculationTest: leaving at 16:30 is 30 minutes of early leave', function (): void {
    $day = attendanceDayFor(attendanceDayEmployee(), '2026-11-02', ['08:00:00', '16:30:00']);

    expect($day->early_leave_minutes)->toBe(30)
        ->and($day->overtime_minutes)->toBe(0);
});

it('AttendanceDayCalculationTest: leaving at 18:00 is 60 minutes of overtime with a pending normal request', function (): void {
    $employee = attendanceDayEmployee();

    $day = attendanceDayFor($employee, '2026-11-02', ['08:00:00', '18:00:00']);
    $request = attendanceOvertimeFor($employee, '2026-11-02');

    expect($day->overtime_minutes)->toBe(60)
        ->and($day->worked_minutes)->toBe(540)
        ->and($request)->not->toBeNull()
        ->and($request->minutes)->toBe(60)
        ->and($request->status)->toBe(OvertimeStatus::Pending)
        ->and($request->day_type)->toBe(OvertimeDayType::Normal)
        ->and((float) $request->multiplier)->toBe(1.25);
});

it('AttendanceDayCalculationTest: overtime below the minimum is not counted', function (): void {
    $employee = attendanceDayEmployee();

    $day = attendanceDayFor($employee, '2026-11-02', ['08:00:00', '17:20:00']);

    expect($day->overtime_minutes)->toBe(0)
        ->and(attendanceOvertimeFor($employee, '2026-11-02'))->toBeNull();
});

it('AttendanceDayCalculationTest: worked minutes subtract only the lunch the employee punched across', function (): void {
    $employee = attendanceDayEmployee();

    expect(attendanceDayFor($employee, '2026-11-02', ['10:00:00', '12:30:00'])->worked_minutes)->toBe(120)
        ->and(attendanceDayFor($employee, '2026-11-03', ['08:00:00', '11:00:00'])->worked_minutes)->toBe(180)
        ->and(attendanceDayFor($employee, '2026-11-04', ['11:00:00', '14:00:00'])->worked_minutes)->toBe(120);
});

it('AttendanceDayCalculationTest: all worked time on a rest day is overtime at the rest multiplier', function (): void {
    $employee = attendanceDayEmployee();

    $day = attendanceDayFor($employee, '2026-11-07', ['09:00:00', '13:00:00']);
    $request = attendanceOvertimeFor($employee, '2026-11-07');

    expect($day->status)->toBe(DayStatus::Present)
        ->and($day->worked_minutes)->toBe(240)
        ->and($day->expected_minutes)->toBe(0)
        ->and($day->late_minutes)->toBe(0)
        ->and($day->early_leave_minutes)->toBe(0)
        ->and($day->overtime_minutes)->toBe(240)
        ->and($request->minutes)->toBe(240)
        ->and($request->day_type)->toBe(OvertimeDayType::Rest)
        ->and((float) $request->multiplier)->toBe(1.5);
});

it('AttendanceDayCalculationTest: all worked time on a holiday is overtime at the holiday multiplier', function (): void {
    $employee = attendanceDayEmployee();
    AttendanceFixtures::holiday('2026-11-02', '2026-11-02');

    $day = attendanceDayFor($employee, '2026-11-02', ['09:00:00', '12:00:00']);
    $request = attendanceOvertimeFor($employee, '2026-11-02');

    expect($day->status)->toBe(DayStatus::Present)
        ->and($day->late_minutes)->toBe(0)
        ->and($day->early_leave_minutes)->toBe(0)
        ->and($day->overtime_minutes)->toBe(180)
        ->and($request->minutes)->toBe(180)
        ->and($request->day_type)->toBe(OvertimeDayType::Holiday)
        ->and((float) $request->multiplier)->toBe(1.5);
});

it('AttendanceDayCalculationTest: holidays and rest days without punches are stored without a request', function (): void {
    $employee = attendanceDayEmployee();
    AttendanceFixtures::holiday('2026-11-02', '2026-11-02');

    $calculator = app(AttendanceDayCalculator::class);
    $holiday = $calculator->calculate($employee, Carbon::parse('2026-11-02', 'Asia/Muscat'));
    $rest = $calculator->calculate($employee, Carbon::parse('2026-11-07', 'Asia/Muscat'));

    expect($holiday->exists)->toBeTrue()
        ->and($holiday->status)->toBe(DayStatus::Holiday)
        ->and($holiday->worked_minutes)->toBe(0)
        ->and($rest->exists)->toBeTrue()
        ->and($rest->status)->toBe(DayStatus::Rest)
        ->and(OvertimeRequest::query()->where('employee_id', $employee->id)->count())->toBe(0);
});

it('AttendanceDayCalculationTest: a working day without punches is not marked absent by the calculator', function (): void {
    $employee = attendanceDayEmployee();

    $day = app(AttendanceDayCalculator::class)->calculate($employee, Carbon::parse('2026-11-02', 'Asia/Muscat'));

    expect($day->exists)->toBeFalse()
        ->and($day->expected_minutes)->toBe(480)
        ->and(attendanceStoredDay($employee, '2026-11-02'))->toBeNull();
});

it('AttendanceDayCalculationTest: approved leave without punches is a leave day', function (): void {
    if (! Schema::hasTable('time_off_leaves')) {
        $this->markTestSkipped('Time Off is not installed.');
    }

    $employee = attendanceDayEmployee();

    $leaveId = DB::table('time_off_leaves')->insertGetId([
        'employee_id' => $employee->id,
        'state'       => 'validate_two',
        'date_from'   => '2026-11-02 00:00:00',
        'date_to'     => '2026-11-03 23:59:59',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
    DB::table('time_off_leaves')->insert([
        'employee_id' => $employee->id,
        'state'       => 'refuse',
        'date_from'   => '2026-11-04 00:00:00',
        'date_to'     => '2026-11-04 23:59:59',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $calculator = app(AttendanceDayCalculator::class);
    $leave = $calculator->calculate($employee, Carbon::parse('2026-11-03', 'Asia/Muscat'));
    $refused = $calculator->calculate($employee, Carbon::parse('2026-11-04', 'Asia/Muscat'));

    expect($leave->exists)->toBeTrue()
        ->and($leave->status)->toBe(DayStatus::Leave)
        ->and($leave->leave_id)->toBe($leaveId)
        ->and($refused->exists)->toBeFalse();
});

it('AttendanceDayCalculationTest: a single punch is present with no worked time and no overtime', function (): void {
    $employee = attendanceDayEmployee();

    $day = attendanceDayFor($employee, '2026-11-02', ['08:25:00']);

    expect($day->status)->toBe(DayStatus::Present)
        ->and($day->first_in->format('H:i'))->toBe('04:25')
        ->and($day->last_out)->toBeNull()
        ->and($day->worked_minutes)->toBe(0)
        ->and($day->late_minutes)->toBe(15)
        ->and($day->early_leave_minutes)->toBe(0)
        ->and($day->overtime_minutes)->toBe(0)
        ->and(attendanceOvertimeFor($employee, '2026-11-02'))->toBeNull();
});

it('AttendanceDayCalculationTest: a flexible employee has no lateness or early leave and overtime only beyond the expected minutes', function (): void {
    $employee = attendanceDayEmployee(['is_flexible' => true]);

    $short = attendanceDayFor($employee, '2026-11-02', ['10:00:00', '15:00:00']);
    $long = attendanceDayFor($employee, '2026-11-03', ['09:30:00', '19:00:00']);
    $slightlyLong = attendanceDayFor($employee, '2026-11-04', ['09:30:00', '18:20:00']);
    $fullyFlexible = attendanceDayFor(attendanceDayEmployee(['is_fully_flexible' => true]), '2026-11-02', ['11:00:00', '15:00:00']);

    expect($short->late_minutes)->toBe(0)
        ->and($short->early_leave_minutes)->toBe(0)
        ->and($short->overtime_minutes)->toBe(0)
        ->and($long->worked_minutes)->toBe(510)
        ->and($long->overtime_minutes)->toBe(30)
        ->and(attendanceOvertimeFor($employee, '2026-11-03')?->minutes)->toBe(30)
        ->and($slightlyLong->overtime_minutes)->toBe(0)
        ->and(attendanceOvertimeFor($employee, '2026-11-04'))->toBeNull()
        ->and($fullyFlexible->late_minutes)->toBe(0)
        ->and($fullyFlexible->early_leave_minutes)->toBe(0);
});

it('AttendanceDayCalculationTest: without a schedule punches are informational only', function (): void {
    Calendar::query()->update(['is_active' => false]);

    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    $day = attendanceDayFor($employee, '2026-11-02', ['09:00:00', '20:00:00']);

    expect($day->status)->toBe(DayStatus::Present)
        ->and($day->worked_minutes)->toBe(660)
        ->and($day->expected_minutes)->toBe(0)
        ->and($day->late_minutes)->toBe(0)
        ->and($day->early_leave_minutes)->toBe(0)
        ->and($day->overtime_minutes)->toBe(0)
        ->and(attendanceOvertimeFor($employee, '2026-11-02'))->toBeNull();
});

it('AttendanceDayCalculationTest: recording the same punch twice returns the same row', function (): void {
    $employee = attendanceDayEmployee();
    $recorder = app(PunchRecorder::class);
    $at = Carbon::parse('2026-11-02 08:00:00', 'Asia/Muscat');

    $first = $recorder->record($employee, $at, PunchSource::Device, meta: ['device_ref' => 'ZK-1']);
    $second = $recorder->record($employee, $at->copy()->utc(), PunchSource::Device);

    expect($second->id)->toBe($first->id)
        ->and($first->device_ref)->toBe('ZK-1')
        ->and($first->punched_at->format('Y-m-d H:i:s'))->toBe('2026-11-02 04:00:00')
        ->and(AttendancePunch::query()->where('employee_id', $employee->id)->count())->toBe(1)
        ->and(attendanceStoredDay($employee, '2026-11-02')?->first_in?->format('H:i'))->toBe('04:00');
});

it('AttendanceDayCalculationTest: punches near local midnight land on their local day', function (): void {
    $employee = attendanceDayEmployee();
    $recorder = app(PunchRecorder::class);

    // 19:30 UTC is 23:30 in Muscat on the same date; 20:10 UTC is 00:10 on the next local date.
    $lateNight = $recorder->record($employee, Carbon::parse('2026-11-02 19:30:00', 'UTC'), PunchSource::Self);
    $afterMidnight = $recorder->record($employee, Carbon::parse('2026-11-02 20:10:00', 'UTC'), PunchSource::Self);

    $monday = attendanceStoredDay($employee, '2026-11-02');
    $tuesday = attendanceStoredDay($employee, '2026-11-03');

    expect($monday)->not->toBeNull()
        ->and($monday->first_in->format('Y-m-d H:i:s'))->toBe($lateNight->punched_at->format('Y-m-d H:i:s'))
        ->and($monday->last_out)->toBeNull()
        ->and($tuesday)->not->toBeNull()
        ->and($tuesday->first_in->format('Y-m-d H:i:s'))->toBe('2026-11-02 20:10:00')
        ->and($tuesday->first_in->format('Y-m-d H:i:s'))->toBe($afterMidnight->punched_at->format('Y-m-d H:i:s'))
        ->and(AttendanceDay::query()->where('employee_id', $employee->id)->count())->toBe(2);
});

it('AttendanceDayCalculationTest: the employee time zone decides the local day', function (): void {
    $employee = attendanceDayEmployee(['time_zone' => 'UTC']);

    app(PunchRecorder::class)->record($employee, Carbon::parse('2026-11-02 20:10:00', 'UTC'), PunchSource::Self);

    expect(attendanceStoredDay($employee, '2026-11-02'))->not->toBeNull()
        ->and(attendanceStoredDay($employee, '2026-11-03'))->toBeNull();
});

it('AttendanceDayCalculationTest: unsorted punches and stored directions give the earliest in and the latest out', function (): void {
    $employee = attendanceDayEmployee();
    $recorder = app(PunchRecorder::class);

    $recorder->record($employee, Carbon::parse('2026-11-02 17:00:00', 'Asia/Muscat'), PunchSource::Device, PunchDirection::In);
    $recorder->record($employee, Carbon::parse('2026-11-02 12:05:00', 'Asia/Muscat'), PunchSource::Device, PunchDirection::Out);
    $recorder->record($employee, Carbon::parse('2026-11-02 08:00:00', 'Asia/Muscat'), PunchSource::Device, PunchDirection::Out);

    $day = attendanceStoredDay($employee, '2026-11-02');

    expect($day->first_in->format('H:i'))->toBe('04:00')
        ->and($day->last_out->format('H:i'))->toBe('13:00')
        ->and($day->worked_minutes)->toBe(480)
        ->and($day->late_minutes)->toBe(0)
        ->and($day->early_leave_minutes)->toBe(0);
});

it('AttendanceDayCalculationTest: same-minute duplicates from several sources are never double counted', function (): void {
    $employee = attendanceDayEmployee();
    $recorder = app(PunchRecorder::class);

    foreach ([
        ['08:00:00', PunchSource::Device],
        ['08:00:30', PunchSource::Self],
        ['08:00:00', PunchSource::Manual],
        ['17:00:00', PunchSource::Device],
        ['17:00:00', PunchSource::Self],
    ] as [$time, $source]) {
        $recorder->record($employee, Carbon::parse('2026-11-02 '.$time, 'Asia/Muscat'), $source);
    }

    $day = attendanceStoredDay($employee, '2026-11-02');

    expect($day->worked_minutes)->toBe(480)
        ->and($day->overtime_minutes)->toBe(0)
        ->and(AttendanceDay::query()->where('employee_id', $employee->id)->count())->toBe(1);

    // Two taps within the same minute are one punch: no distinct check-out, so no early leave.
    $doubleTap = attendanceDayFor($employee, '2026-11-03', ['08:00:00', '08:00:40']);

    expect($doubleTap->last_out)->toBeNull()
        ->and($doubleTap->worked_minutes)->toBe(0)
        ->and($doubleTap->early_leave_minutes)->toBe(0);
});

it('AttendanceDayCalculationTest: a manual override row is not recomputed by a later punch', function (): void {
    $employee = attendanceDayEmployee();

    $override = AttendanceDay::query()->create([
        'employee_id'        => $employee->id,
        'date'               => '2026-11-02',
        'status'             => DayStatus::Absent,
        'worked_minutes'     => 0,
        'is_manual_override' => true,
        'override_note'      => 'HR decision',
    ]);

    $punch = app(PunchRecorder::class)->record($employee, Carbon::parse('2026-11-02 08:00:00', 'Asia/Muscat'), PunchSource::Self);
    app(PunchRecorder::class)->record($employee, Carbon::parse('2026-11-02 19:00:00', 'Asia/Muscat'), PunchSource::Self);

    $day = $override->fresh();

    expect($punch->exists)->toBeTrue()
        ->and($day->status)->toBe(DayStatus::Absent)
        ->and($day->first_in)->toBeNull()
        ->and($day->worked_minutes)->toBe(0)
        ->and($day->is_manual_override)->toBeTrue()
        ->and(attendanceOvertimeFor($employee, '2026-11-02'))->toBeNull();
});

it('AttendanceDayCalculationTest: a decided request is untouched while a pending one follows the minutes', function (): void {
    $employee = attendanceDayEmployee();
    $recorder = app(PunchRecorder::class);
    $calculator = app(AttendanceDayCalculator::class);

    // Pending: follows the recalculated minutes.
    $recorder->record($employee, Carbon::parse('2026-11-02 08:00:00', 'Asia/Muscat'), PunchSource::Self);
    $recorder->record($employee, Carbon::parse('2026-11-02 18:00:00', 'Asia/Muscat'), PunchSource::Self);
    $pending = attendanceOvertimeFor($employee, '2026-11-02');

    $lastPunch = $recorder->record($employee, Carbon::parse('2026-11-02 19:00:00', 'Asia/Muscat'), PunchSource::Self);

    expect($pending->fresh()->minutes)->toBe(120)
        ->and(attendanceOvertimeFor($employee, '2026-11-02')->id)->toBe($pending->id);

    // ... and is deleted when the overtime drops below the minimum.
    AttendancePunch::query()->where('employee_id', $employee->id)->where('punched_at', '>=', '2026-11-02 14:00:00')->delete();
    $recorder->record($employee, Carbon::parse('2026-11-02 17:15:00', 'Asia/Muscat'), PunchSource::Self);

    expect($lastPunch->fresh())->toBeNull()
        ->and(attendanceStoredDay($employee, '2026-11-02')->overtime_minutes)->toBe(0)
        ->and(attendanceOvertimeFor($employee, '2026-11-02'))->toBeNull();

    // Decided: never changed or deleted by recalculation.
    $recorder->record($employee, Carbon::parse('2026-11-03 08:00:00', 'Asia/Muscat'), PunchSource::Self);
    $recorder->record($employee, Carbon::parse('2026-11-03 18:00:00', 'Asia/Muscat'), PunchSource::Self);
    $approved = attendanceOvertimeFor($employee, '2026-11-03');
    $approved->update(['status' => OvertimeStatus::Approved, 'decided_at' => now()]);

    $recorder->record($employee, Carbon::parse('2026-11-03 20:00:00', 'Asia/Muscat'), PunchSource::Self);

    expect($approved->fresh()->minutes)->toBe(60)
        ->and($approved->fresh()->status)->toBe(OvertimeStatus::Approved)
        ->and(attendanceStoredDay($employee, '2026-11-03')->overtime_minutes)->toBe(180);

    AttendancePunch::query()->where('employee_id', $employee->id)->where('punched_at', '>=', '2026-11-03 14:00:00')->delete();
    $calculator->calculate($employee, Carbon::parse('2026-11-03', 'Asia/Muscat'));

    expect($approved->fresh())->not->toBeNull()
        ->and($approved->fresh()->minutes)->toBe(60);
});

it('AttendanceDayCalculationTest: a pending request is removed when the day has no overtime', function (): void {
    $employee = attendanceDayEmployee();

    $stale = OvertimeRequest::factory()->create([
        'employee_id' => $employee->id,
        'date'        => '2026-11-02',
        'minutes'     => 99,
    ]);

    attendanceDayFor($employee, '2026-11-02', ['08:00:00', '17:00:00']);

    expect(OvertimeRequest::query()->find($stale->id))->toBeNull();
});

it('AttendanceDayCalculationTest: nothing is created for a departed or inactive employee', function (string $case): void {
    $employee = attendanceDayEmployee($case === 'departed'
        ? ['departure_date' => '2026-11-01']
        : ['is_active' => false]);

    $day = attendanceDayFor($employee, '2026-11-02', ['08:30:00', '19:00:00']);

    expect($day->exists)->toBeFalse()
        ->and(AttendanceDay::query()->where('employee_id', $employee->id)->count())->toBe(0)
        ->and(OvertimeRequest::query()->where('employee_id', $employee->id)->count())->toBe(0);
})->with(['departed', 'inactive']);

it('AttendanceDayCalculationTest: the departure date itself is still calculated', function (): void {
    $employee = attendanceDayEmployee(['departure_date' => '2026-11-02']);

    expect(attendanceDayFor($employee, '2026-11-02', ['08:30:00', '17:00:00'])->late_minutes)->toBe(20);
});

it('AttendanceDayCalculationTest: a lone punch at or after the shift midpoint is a missing check-in, not lateness', function (): void {
    $employee = attendanceDayEmployee();

    // Shift 08:00-17:00: the midpoint is 12:30.
    $checkOut = attendanceDayFor($employee, '2026-11-02', ['17:00:00']);
    $atMidpoint = attendanceDayFor($employee, '2026-11-03', ['12:30:00']);
    $beforeMidpoint = attendanceDayFor($employee, '2026-11-04', ['12:29:00']);
    $morning = attendanceDayFor($employee, '2026-11-05', ['08:25:00']);

    expect($checkOut->status)->toBe(DayStatus::Present)
        ->and($checkOut->late_minutes)->toBe(0)
        ->and($checkOut->worked_minutes)->toBe(0)
        ->and($checkOut->overtime_minutes)->toBe(0)
        ->and($atMidpoint->late_minutes)->toBe(0)
        ->and($beforeMidpoint->late_minutes)->toBe(12 * 60 + 29 - (8 * 60 + 10))
        ->and($morning->late_minutes)->toBe(15)
        ->and(OvertimeRequest::query()->where('employee_id', $employee->id)->count())->toBe(0);
});

it('AttendanceDayCalculationTest: lateness never exceeds the expected minutes of the day', function (): void {
    // Raw lateness would be 17:30 - 08:10 = 560 minutes against 480 expected.
    $day = attendanceDayFor(attendanceDayEmployee(), '2026-11-02', ['17:30:00', '17:45:00']);

    expect($day->expected_minutes)->toBe(480)
        ->and($day->late_minutes)->toBe(480);
});

/**
 * Pins "now" after the November test dates used by the stale-row tests, so 2026-11-02..06 are closed days.
 */
function attendanceTravelAfterTestDays(): void
{
    test()->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Muscat'));
}

function attendanceRecalculate(Employee $employee, string $date): AttendanceDay
{
    return app(AttendanceDayCalculator::class)->calculate($employee, Carbon::parse($date, 'Asia/Muscat'));
}

it('AttendanceDayCalculationTest: a leave day whose leave is refused or removed is no longer a leave day', function (): void {
    attendanceTravelAfterTestDays();
    $employee = attendanceDayEmployee();

    $leave = fn (string $date): int => DB::table('time_off_leaves')->insertGetId([
        'employee_id' => $employee->id,
        'state'       => 'validate_two',
        'date_from'   => $date.' 00:00:00',
        'date_to'     => $date.' 23:59:59',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // A closed (past) day becomes absent in place.
    $pastLeaveId = $leave('2026-11-02');
    $stored = attendanceRecalculate($employee, '2026-11-02');
    expect($stored->status)->toBe(DayStatus::Leave);

    DB::table('time_off_leaves')->where('id', $pastLeaveId)->update(['state' => 'refuse']);
    $past = attendanceRecalculate($employee, '2026-11-02');

    expect($past->exists)->toBeTrue()
        ->and($past->id)->toBe($stored->id)
        ->and($past->status)->toBe(DayStatus::Absent)
        ->and($past->leave_id)->toBeNull()
        ->and($past->expected_minutes)->toBe(480)
        ->and(attendanceStoredDay($employee, '2026-11-02')->status)->toBe(DayStatus::Absent);

    // An open (today or later) day is left for the nightly close: the stale row is removed.
    $futureLeaveId = $leave('2026-11-23');
    expect(attendanceRecalculate($employee, '2026-11-23')->status)->toBe(DayStatus::Leave);

    DB::table('time_off_leaves')->where('id', $futureLeaveId)->delete();
    $future = attendanceRecalculate($employee, '2026-11-23');

    expect($future->exists)->toBeFalse()
        ->and($future->status)->toBe(DayStatus::Absent)
        ->and(attendanceStoredDay($employee, '2026-11-23'))->toBeNull();
});

it('AttendanceDayCalculationTest: a holiday day whose holiday is deleted is no longer a holiday', function (): void {
    attendanceTravelAfterTestDays();
    $employee = attendanceDayEmployee();
    $holiday = AttendanceFixtures::holiday('2026-11-03', '2026-11-03');

    expect(attendanceRecalculate($employee, '2026-11-03')->status)->toBe(DayStatus::Holiday);

    $holiday->delete();
    $day = attendanceRecalculate($employee, '2026-11-03');

    expect($day->status)->toBe(DayStatus::Absent)
        ->and(attendanceStoredDay($employee, '2026-11-03')->status)->toBe(DayStatus::Absent);
});

it('AttendanceDayCalculationTest: a present day whose punches are removed is reset and its pending request deleted', function (): void {
    attendanceTravelAfterTestDays();
    $employee = attendanceDayEmployee();

    attendanceDayFor($employee, '2026-11-04', ['08:00:00', '19:00:00']);
    attendanceDayFor($employee, '2026-11-05', ['08:00:00', '19:00:00']);
    $pending = attendanceOvertimeFor($employee, '2026-11-04');
    $approved = attendanceOvertimeFor($employee, '2026-11-05');
    $approved->update(['status' => OvertimeStatus::Approved, 'decided_at' => now()]);

    expect($pending->minutes)->toBe(120);

    AttendancePunch::query()->where('employee_id', $employee->id)->delete();

    $reset = attendanceRecalculate($employee, '2026-11-04');
    attendanceRecalculate($employee, '2026-11-05');

    expect($reset->status)->toBe(DayStatus::Absent)
        ->and($reset->first_in)->toBeNull()
        ->and($reset->last_out)->toBeNull()
        ->and($reset->worked_minutes)->toBe(0)
        ->and($reset->late_minutes)->toBe(0)
        ->and($reset->early_leave_minutes)->toBe(0)
        ->and($reset->overtime_minutes)->toBe(0)
        ->and(OvertimeRequest::query()->find($pending->id))->toBeNull()
        ->and($approved->fresh()->status)->toBe(OvertimeStatus::Approved)
        ->and($approved->fresh()->minutes)->toBe(120)
        ->and(attendanceStoredDay($employee, '2026-11-05')->overtime_minutes)->toBe(0);
});

it('AttendanceDayCalculationTest: without a schedule a day whose punches are removed is deleted', function (): void {
    attendanceTravelAfterTestDays();
    Calendar::query()->update(['is_active' => false]);
    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    attendanceDayFor($employee, '2026-11-02', ['09:00:00', '20:00:00']);
    AttendancePunch::query()->where('employee_id', $employee->id)->delete();

    $day = attendanceRecalculate($employee, '2026-11-02');

    expect($day->exists)->toBeFalse()
        ->and($day->status)->toBeNull()
        ->and(attendanceStoredDay($employee, '2026-11-02'))->toBeNull();
});

it('AttendanceDayCalculationTest: an absent row on an open day is kept', function (): void {
    attendanceTravelAfterTestDays();
    $employee = attendanceDayEmployee();

    $absent = AttendanceDay::query()->create([
        'employee_id'      => $employee->id,
        'date'             => '2026-11-20',
        'status'           => DayStatus::Absent,
        'expected_minutes' => 480,
    ]);

    $day = attendanceRecalculate($employee, '2026-11-20');

    expect($day->id)->toBe($absent->id)
        ->and($day->status)->toBe(DayStatus::Absent);
});

it('AttendanceDayCalculationTest: a manual override row without any basis is not reset', function (): void {
    attendanceTravelAfterTestDays();
    $employee = attendanceDayEmployee();

    $override = AttendanceDay::query()->create([
        'employee_id'        => $employee->id,
        'date'               => '2026-11-02',
        'status'             => DayStatus::Leave,
        'worked_minutes'     => 120,
        'overtime_minutes'   => 60,
        'is_manual_override' => true,
    ]);
    $pending = OvertimeRequest::factory()->create(['employee_id' => $employee->id, 'date' => '2026-11-02']);

    attendanceRecalculate($employee, '2026-11-02');

    $day = $override->fresh();

    expect($day->status)->toBe(DayStatus::Leave)
        ->and($day->worked_minutes)->toBe(120)
        ->and($day->overtime_minutes)->toBe(60)
        ->and(OvertimeRequest::query()->find($pending->id))->not->toBeNull();
});
