<?php

namespace Tests\Helpers;

use Illuminate\Support\Carbon;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Calendar;
use Webkul\Support\Models\CalendarAttendance;
use Webkul\Support\Models\CalendarLeave;
use Webkul\Support\Models\Company;

class AttendanceFixtures
{
    /**
     * Monday to Friday, 08:00-17:00 with a 12:00-13:00 lunch: [day_period, hour_from, hour_to] rows per weekday.
     *
     * @var array<string, list<array{0: string, 1: string, 2: string}>>
     */
    public const DEFAULT_WEEKDAYS = [
        'monday'    => [['morning', '8', '12'], ['lunch', '12', '13'], ['afternoon', '13', '17']],
        'tuesday'   => [['morning', '8', '12'], ['lunch', '12', '13'], ['afternoon', '13', '17']],
        'wednesday' => [['morning', '8', '12'], ['lunch', '12', '13'], ['afternoon', '13', '17']],
        'thursday'  => [['morning', '8', '12'], ['lunch', '12', '13'], ['afternoon', '13', '17']],
        'friday'    => [['morning', '8', '12'], ['lunch', '12', '13'], ['afternoon', '13', '17']],
    ];

    /**
     * Production runs with APP_TIMEZONE=Asia/Muscat but the test environment falls back to UTC: pin the
     * application zone so "local" punch times in tests mean what they mean in production.
     */
    public static function useLocalTimezone(string $timezone = 'Asia/Muscat'): void
    {
        config(['app.timezone' => $timezone]);
    }

    public static function company(): Company
    {
        return Company::factory()->create(['currency_id' => null]);
    }

    /**
     * @param  array<string, list<array{0: string, 1: string, 2: string}>>  $weekdays
     */
    public static function calendar(?Company $company = null, array $weekdays = self::DEFAULT_WEEKDAYS, bool $twoWeeks = false): Calendar
    {
        $calendar = Calendar::query()->create([
            'name'               => 'Attendance Test Calendar '.uniqid(),
            'timezone'           => 'Asia/Muscat',
            'hours_per_day'      => 8,
            'is_active'          => true,
            'two_weeks_calendar' => $twoWeeks,
            'flexible_hours'     => false,
            'company_id'         => $company?->id,
        ]);

        foreach ($weekdays as $day => $rows) {
            foreach ($rows as [$period, $from, $to]) {
                CalendarAttendance::query()->create([
                    'name'        => ucfirst($day).' '.$period,
                    'day_of_week' => $day,
                    'day_period'  => $period,
                    'hour_from'   => $from,
                    'hour_to'     => $to,
                    'calendar_id' => $calendar->id,
                ]);
            }
        }

        return $calendar;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function employee(Company $company, ?Calendar $calendar, array $attributes = []): Employee
    {
        $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

        return Employee::query()->create(array_merge([
            'company_id'  => $company->id,
            'calendar_id' => $calendar?->id,
            'user_id'     => $user->id,
            'time_zone'   => null,
            'name'        => 'Attendance Test Employee '.uniqid(),
            'work_email'  => 'attendance-'.uniqid().'@test.example',
            'is_active'   => true,
        ], $attributes));
    }

    /**
     * Public holiday covering the dates (inclusive), for every calendar unless one is given.
     */
    public static function holiday(string $from, string $to, ?int $calendarId = null): CalendarLeave
    {
        return CalendarLeave::query()->create([
            'name'        => 'Holiday '.uniqid(),
            'time_type'   => 'leave',
            'date_from'   => $from.' 00:00:00',
            'date_to'     => $to.' 23:59:59',
            'calendar_id' => $calendarId,
        ]);
    }

    /**
     * Inserts the punch directly (no day recalculation); $localDateTime is read in the employee's zone.
     */
    public static function punch(Employee $employee, string $localDateTime, PunchSource $source = PunchSource::Manual): AttendancePunch
    {
        $zone = $employee->time_zone ?: config('app.timezone');

        return AttendancePunch::query()->create([
            'employee_id' => $employee->id,
            'punched_at'  => Carbon::parse($localDateTime, $zone)->utc(),
            'source'      => $source,
        ]);
    }
}
