<?php

use Illuminate\Support\Carbon;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Services\WorkScheduleResolver;
use Webkul\Support\Models\Calendar;
use Webkul\Support\Models\CalendarAttendance;
use Webkul\Support\Models\CalendarLeave;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();
});

/**
 * Sunday to Thursday, 08:00-17:00 with a 12:00-13:00 lunch (Friday and Saturday have no rows).
 *
 * @return array<string, list<array{0: string, 1: string, 2: string}>>
 */
function attendanceOmaniWeek(): array
{
    $day = [['morning', '8', '12'], ['lunch', '12', '13'], ['afternoon', '13', '17']];

    return ['sunday' => $day, 'monday' => $day, 'tuesday' => $day, 'wednesday' => $day, 'thursday' => $day];
}

function attendanceWorkDay(object $employee, string $date): object
{
    return app(WorkScheduleResolver::class)->forEmployee($employee, Carbon::parse($date, 'Asia/Muscat'));
}

it('AttendanceScheduleTest: a weekday counts the morning and afternoon rows and excludes lunch', function (): void {
    $company = AttendanceFixtures::company();
    $employee = AttendanceFixtures::employee($company, AttendanceFixtures::calendar($company));

    // 2026-11-02 is a Monday.
    $workDay = attendanceWorkDay($employee, '2026-11-02');

    expect($workDay->hasSchedule)->toBeTrue()
        ->and($workDay->isWorkingDay)->toBeTrue()
        ->and($workDay->isRestDay)->toBeFalse()
        ->and($workDay->isHoliday)->toBeFalse()
        ->and($workDay->holidayName)->toBeNull()
        ->and($workDay->startMinute)->toBe(8 * 60)
        ->and($workDay->endMinute)->toBe(17 * 60)
        ->and($workDay->expectedMinutes)->toBe(480)
        ->and($workDay->lunch)->toBe([['from' => 720, 'to' => 780]]);
});

it('AttendanceScheduleTest: decimal hours are read as fractions of an hour', function (): void {
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company, [
        'monday' => [['morning', '7.5', '12'], ['lunch', '12', '12.5'], ['afternoon', '12.5', '16.75']],
    ]);
    $employee = AttendanceFixtures::employee($company, $calendar);

    $workDay = attendanceWorkDay($employee, '2026-11-02');

    expect($workDay->startMinute)->toBe(450)
        ->and($workDay->endMinute)->toBe(1005)
        ->and($workDay->expectedMinutes)->toBe(270 + 255)
        ->and($workDay->lunch)->toBe([['from' => 720, 'to' => 750]]);
});

it('AttendanceScheduleTest: a weekday without rows is a weekly rest day', function (): void {
    $company = AttendanceFixtures::company();
    $employee = AttendanceFixtures::employee($company, AttendanceFixtures::calendar($company, attendanceOmaniWeek()));

    // 2026-11-06 is a Friday; 2026-11-01 a Sunday.
    $friday = attendanceWorkDay($employee, '2026-11-06');
    $sunday = attendanceWorkDay($employee, '2026-11-01');

    expect($friday->hasSchedule)->toBeTrue()
        ->and($friday->isRestDay)->toBeTrue()
        ->and($friday->isWorkingDay)->toBeFalse()
        ->and($friday->expectedMinutes)->toBe(0)
        ->and($friday->startMinute)->toBeNull()
        ->and($friday->endMinute)->toBeNull()
        ->and($sunday->isWorkingDay)->toBeTrue()
        ->and($sunday->expectedMinutes)->toBe(480);
});

it('AttendanceScheduleTest: a two-week calendar keeps only the rows of the date week type', function (): void {
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company, [], twoWeeks: true);

    $rows = [
        ['morning', '8', '12', '0'],
        ['morning', '8', '12', '1'],
        ['lunch', '12', '13', null],
        ['afternoon', '13', '17', '1'],
    ];

    foreach ($rows as [$period, $from, $to, $weekType]) {
        CalendarAttendance::query()->create([
            'name'        => 'Monday '.$period,
            'day_of_week' => 'monday',
            'day_period'  => $period,
            'week_type'   => $weekType,
            'hour_from'   => $from,
            'hour_to'     => $to,
            'calendar_id' => $calendar->id,
        ]);
    }

    $employee = AttendanceFixtures::employee($company, $calendar);

    $first = Carbon::parse('2026-11-02');
    $second = Carbon::parse('2026-11-09');

    expect(CalendarAttendance::getWeekType($first))->not->toBe(CalendarAttendance::getWeekType($second));

    foreach ([$first, $second] as $monday) {
        $workDay = attendanceWorkDay($employee, $monday->toDateString());

        if (CalendarAttendance::getWeekType($monday) === 1) {
            expect($workDay->expectedMinutes)->toBe(480)
                ->and($workDay->endMinute)->toBe(17 * 60);
        } else {
            expect($workDay->expectedMinutes)->toBe(240)
                ->and($workDay->endMinute)->toBe(12 * 60);
        }

        expect($workDay->lunch)->toBe([['from' => 720, 'to' => 780]]);
    }
});

it('AttendanceScheduleTest: the calendar falls back from the employee to the company to a company-less one', function (): void {
    Calendar::query()->update(['is_active' => false]);

    $resolver = app(WorkScheduleResolver::class);
    $company = AttendanceFixtures::company();
    $own = AttendanceFixtures::calendar($company);
    $companyCalendar = AttendanceFixtures::calendar($company);
    $laterCompanyCalendar = AttendanceFixtures::calendar($company);
    AttendanceFixtures::calendar(AttendanceFixtures::company());

    $withOwn = AttendanceFixtures::employee($company, $own);
    $withoutOwn = AttendanceFixtures::employee($company, null);

    expect($resolver->calendarFor($withOwn)?->id)->toBe($own->id)
        ->and($resolver->calendarFor($withoutOwn)?->id)->toBe($own->id);

    // The lowest-id active calendar of the company wins; inactive ones are skipped.
    $own->update(['is_active' => false]);

    expect($resolver->calendarFor($withoutOwn)?->id)->toBe($companyCalendar->id);

    $companyCalendar->update(['is_active' => false]);
    $laterCompanyCalendar->update(['is_active' => false]);

    // No active company calendar: the lowest-id active calendar without a company, never another company's.
    expect($resolver->calendarFor($withoutOwn))->toBeNull();

    $shared = AttendanceFixtures::calendar(null);
    $laterShared = AttendanceFixtures::calendar(null);

    expect($resolver->calendarFor($withoutOwn)?->id)->toBe($shared->id)
        ->and($laterShared->id)->toBeGreaterThan($shared->id);
});

it('AttendanceScheduleTest: without any calendar there is no schedule', function (): void {
    Calendar::query()->update(['is_active' => false]);

    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    $workDay = attendanceWorkDay($employee, '2026-11-02');

    expect($workDay->hasSchedule)->toBeFalse()
        ->and($workDay->isWorkingDay)->toBeFalse()
        ->and($workDay->isRestDay)->toBeFalse()
        ->and($workDay->startMinute)->toBeNull()
        ->and($workDay->endMinute)->toBeNull()
        ->and($workDay->expectedMinutes)->toBe(0)
        ->and($workDay->lunch)->toBe([]);
});

it('AttendanceScheduleTest: holidays apply by calendar and company scope over their date range', function (): void {
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company);
    $otherCalendar = AttendanceFixtures::calendar($company);
    $employee = AttendanceFixtures::employee($company, $calendar);

    $everywhere = AttendanceFixtures::holiday('2026-11-18', '2026-11-19');
    $mine = AttendanceFixtures::holiday('2026-11-23', '2026-11-23', $calendar->id);
    AttendanceFixtures::holiday('2026-11-24', '2026-11-24', $otherCalendar->id);
    AttendanceFixtures::holiday('2026-11-25', '2026-11-25')->update(['company_id' => AttendanceFixtures::company()->id]);
    $myCompany = AttendanceFixtures::holiday('2026-11-26', '2026-11-26');
    $myCompany->update(['company_id' => $company->id]);

    // Personal time off rows (with a resource) and other time types are not public holidays.
    CalendarLeave::query()->create([
        'name'          => 'Personal', 'time_type' => 'leave', 'date_from' => '2026-11-30 00:00:00', 'date_to' => '2026-11-30 23:59:59',
        'resource_type' => 'employee', 'resource_id' => $employee->id,
    ]);
    CalendarLeave::query()->create([
        'name' => 'Other', 'time_type' => 'other', 'date_from' => '2026-12-01 00:00:00', 'date_to' => '2026-12-01 23:59:59',
    ]);

    $wednesday = attendanceWorkDay($employee, '2026-11-18');
    $thursday = attendanceWorkDay($employee, '2026-11-19');

    expect($wednesday->isHoliday)->toBeTrue()
        ->and($wednesday->holidayName)->toBe($everywhere->name)
        ->and($wednesday->isWorkingDay)->toBeFalse()
        ->and($wednesday->expectedMinutes)->toBe(0)
        ->and($thursday->isHoliday)->toBeTrue()
        ->and(attendanceWorkDay($employee, '2026-11-17')->isHoliday)->toBeFalse()
        ->and(attendanceWorkDay($employee, '2026-11-20')->isHoliday)->toBeFalse()
        ->and(attendanceWorkDay($employee, '2026-11-23')->holidayName)->toBe($mine->name)
        ->and(attendanceWorkDay($employee, '2026-11-24')->isHoliday)->toBeFalse()
        ->and(attendanceWorkDay($employee, '2026-11-25')->isHoliday)->toBeFalse()
        ->and(attendanceWorkDay($employee, '2026-11-26')->holidayName)->toBe($myCompany->name)
        ->and(attendanceWorkDay($employee, '2026-11-30')->isHoliday)->toBeFalse()
        ->and(attendanceWorkDay($employee, '2026-12-01')->isHoliday)->toBeFalse();
});

it('AttendanceScheduleTest: a holiday stored as bare dates covers its last day', function (): void {
    $company = AttendanceFixtures::company();
    $employee = AttendanceFixtures::employee($company, AttendanceFixtures::calendar($company));

    CalendarLeave::query()->create([
        'name' => 'Bare dates', 'time_type' => 'leave', 'date_from' => '2026-12-14', 'date_to' => '2026-12-15',
    ]);

    expect(attendanceWorkDay($employee, '2026-12-14')->isHoliday)->toBeTrue()
        ->and(attendanceWorkDay($employee, '2026-12-15')->isHoliday)->toBeTrue()
        ->and(attendanceWorkDay($employee, '2026-12-16')->isHoliday)->toBeFalse();
});
