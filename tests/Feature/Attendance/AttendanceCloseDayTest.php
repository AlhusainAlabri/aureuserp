<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Console\CloseDayCommand;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Employee\Models\Employee;
use Webkul\Support\Models\Calendar;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();

    // The November test dates must be closed days: pin "now" after them (restored after each test).
    $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Muscat'));
});

/**
 * @param  array<string, mixed>  $attributes
 */
function closeDayEmployee(array $attributes = []): Employee
{
    $company = AttendanceFixtures::company();

    return AttendanceFixtures::employee($company, AttendanceFixtures::calendar($company), $attributes);
}

function closeDayRow(Employee $employee, string $date): ?AttendanceDay
{
    return AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', $date)->first();
}

function closeDayRows(Employee $employee): int
{
    return AttendanceDay::query()->where('employee_id', $employee->id)->count();
}

// 2026-11-02 is a Monday, 2026-11-07 a Saturday.

it('AttendanceCloseDayTest: marks a working day without punches, leave or holiday as absent', function (): void {
    $employee = closeDayEmployee();

    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();

    $row = closeDayRow($employee, '2026-11-02');

    expect($row)->not->toBeNull()
        ->and($row->status)->toBe(DayStatus::Absent)
        ->and($row->expected_minutes)->toBe(480)
        ->and($row->worked_minutes)->toBe(0)
        ->and($row->late_minutes)->toBe(0);
});

it('AttendanceCloseDayTest: defaults to yesterday in the application timezone', function (): void {
    $employee = closeDayEmployee();

    // 2026-11-03 02:00 in Muscat is still 2026-11-02 22:00 UTC: yesterday is Monday 2026-11-02 locally.
    $this->travelTo(Carbon::parse('2026-11-03 02:00:00', 'Asia/Muscat'));

    $this->artisan('attendance:close-day')->assertSuccessful();

    expect(closeDayRow($employee, '2026-11-02'))->not->toBeNull()
        ->and(closeDayRow($employee, '2026-11-02')->status)->toBe(DayStatus::Absent)
        ->and(closeDayRows($employee))->toBe(1);
});

it('AttendanceCloseDayTest: a holiday, a rest day and an approved leave day do not become absent', function (): void {
    $holidayEmployee = closeDayEmployee();
    AttendanceFixtures::holiday('2026-11-02', '2026-11-02', $holidayEmployee->calendar_id);

    $restEmployee = closeDayEmployee();

    $leaveEmployee = closeDayEmployee();

    if (Schema::hasTable('time_off_leaves')) {
        DB::table('time_off_leaves')->insert([
            'employee_id' => $leaveEmployee->id,
            'state'       => 'validate_two',
            'date_from'   => '2026-11-02 00:00:00',
            'date_to'     => '2026-11-02 23:59:59',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();
    $this->artisan('attendance:close-day', ['--date' => '2026-11-07'])->assertSuccessful();

    expect(closeDayRow($holidayEmployee, '2026-11-02')?->status)->toBe(DayStatus::Holiday)
        ->and(closeDayRow($restEmployee, '2026-11-07')?->status)->toBe(DayStatus::Rest)
        ->and(AttendanceDay::query()->where('employee_id', $restEmployee->id)->whereDate('date', '2026-11-07')->where('status', DayStatus::Absent->value)->exists())->toBeFalse()
        ->and(AttendanceDay::query()->where('employee_id', $holidayEmployee->id)->where('status', DayStatus::Absent->value)->exists())->toBeFalse();

    if (Schema::hasTable('time_off_leaves')) {
        expect(closeDayRow($leaveEmployee, '2026-11-02')?->status)->toBe(DayStatus::Leave);
    }
});

it('AttendanceCloseDayTest: an employee without a schedule gets no absent row', function (): void {
    Calendar::query()->update(['is_active' => false]);

    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();

    expect(closeDayRows($employee))->toBe(0);
});

it('AttendanceCloseDayTest: inactive and departed employees are skipped', function (): void {
    $inactive = closeDayEmployee(['is_active' => false]);
    $departed = closeDayEmployee(['departure_date' => '2026-10-31']);
    $stillHere = closeDayEmployee(['departure_date' => '2026-11-02']);

    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();

    expect(closeDayRows($inactive))->toBe(0)
        ->and(closeDayRows($departed))->toBe(0)
        ->and(closeDayRow($stillHere, '2026-11-02')?->status)->toBe(DayStatus::Absent);
});

it('AttendanceCloseDayTest: a day with punches keeps its present status', function (): void {
    $employee = closeDayEmployee();
    AttendanceFixtures::punch($employee, '2026-11-02 08:00:00');
    AttendanceFixtures::punch($employee, '2026-11-02 17:00:00');

    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();

    $row = closeDayRow($employee, '2026-11-02');

    expect($row->status)->toBe(DayStatus::Present)
        ->and($row->worked_minutes)->toBe(480);
});

it('AttendanceCloseDayTest: running twice leaves one row per employee and day', function (): void {
    $employee = closeDayEmployee();

    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();
    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();
    $this->artisan('attendance:close-day', ['--date' => '2026-11-03'])->assertSuccessful();

    expect(closeDayRows($employee))->toBe(2)
        ->and(AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', '2026-11-02')->count())->toBe(1);
});

it('AttendanceCloseDayTest: a manual override row is left alone', function (): void {
    $employee = closeDayEmployee();

    AttendanceDay::query()->create([
        'employee_id'        => $employee->id,
        'date'               => '2026-11-02',
        'status'             => DayStatus::Present,
        'worked_minutes'     => 300,
        'expected_minutes'   => 480,
        'is_manual_override' => true,
        'override_note'      => 'HR correction',
    ]);

    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();

    $row = closeDayRow($employee, '2026-11-02');

    expect($row->status)->toBe(DayStatus::Present)
        ->and($row->worked_minutes)->toBe(300)
        ->and(closeDayRows($employee))->toBe(1);
});

it('AttendanceCloseDayTest: a day that is not over in the employee zone is not closed as absent', function (): void {
    $employee = closeDayEmployee(['time_zone' => 'Asia/Muscat']);

    $this->travelTo(Carbon::parse('2026-11-02 10:00:00', 'Asia/Muscat'));

    $this->artisan('attendance:close-day', ['--date' => '2026-11-02'])->assertSuccessful();

    expect(closeDayRows($employee))->toBe(0);
});

it('AttendanceCloseDayTest: rejects a date that is not Y-m-d', function (): void {
    $this->artisan('attendance:close-day', ['--date' => 'not-a-date'])->assertFailed();
    $this->artisan('attendance:close-day', ['--date' => '2026-02-30'])->assertFailed();
    $this->artisan('attendance:close-day', ['--date' => '02/11/2026'])->assertFailed();

    expect(AttendanceDay::query()->count())->toBe(0);
});

it('AttendanceCloseDayTest: reports how many employees were processed and how many absences were written', function (): void {
    closeDayEmployee();
    closeDayEmployee();
    closeDayEmployee(['is_active' => false]);

    $expectedProcessed = Employee::query()->where('is_active', true)->count();

    Artisan::call('attendance:close-day', ['--date' => '2026-11-02']);

    $absent = AttendanceDay::query()->whereDate('date', '2026-11-02')->where('status', DayStatus::Absent->value)->count();

    expect($absent)->toBeGreaterThanOrEqual(2)
        ->and(Artisan::output())->toContain(__('attendances::attendances.console.close_day.summary', [
            'date'      => '2026-11-02',
            'processed' => $expectedProcessed,
            'absent'    => $absent,
        ]));

    // A second run finds the rows already there and writes none.
    Artisan::call('attendance:close-day', ['--date' => '2026-11-02']);

    expect(Artisan::output())->toContain(__('attendances::attendances.console.close_day.summary', [
        'date'      => '2026-11-02',
        'processed' => $expectedProcessed,
        'absent'    => 0,
    ]));
});

it('AttendanceCloseDayTest: the command is registered and scheduled at the configured close time', function (): void {
    expect(array_key_exists('attendance:close-day', Artisan::all()))->toBeTrue()
        ->and(Artisan::all()['attendance:close-day'])->toBeInstanceOf(CloseDayCommand::class);

    // Loads routes/console.php, which registers the scheduled commands.
    app(Kernel::class)->bootstrap();

    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'attendance:close-day'));

    expect($events)->toHaveCount(1);

    /** @var Event $event */
    $event = $events->first();
    $time = app(AttendanceSettings::class)->workday_close_time;
    [$hour, $minute] = array_map('intval', explode(':', $time));

    expect($event->expression)->toBe(sprintf('%d %d * * *', $minute, $hour))
        ->and($event->withoutOverlapping)->toBeTrue();
});
