<?php

namespace Webkul\Attendance\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\Support\Models\Calendar;
use Webkul\Support\Models\CalendarAttendance;
use Webkul\Support\Models\CalendarLeave;

class WorkScheduleResolver
{
    /**
     * The work expected from the employee on the local date: only `$date->toDateString()` is used,
     * so callers pass the date already in the employee's zone.
     */
    public function forEmployee(Employee $employee, CarbonInterface $date): WorkDay
    {
        $dateString = $date->toDateString();
        $calendar = $this->calendarFor($employee);
        $holiday = $this->holidayFor($employee, $calendar, $dateString);

        return $this->buildWorkDay(
            $calendar,
            $holiday,
            $calendar === null ? collect() : $this->rowsFor($calendar, $dateString),
        );
    }

    /**
     * Every day of the month, keyed by `Y-m-d`: the same values as `forEmployee` for each date, but the
     * calendar, its weekday rows and the holidays overlapping the month are loaded once (a handful of
     * queries) and each day is built in memory. Approved leave is not part of a `WorkDay`, so it is not
     * loaded here (see `approvedLeaveId`).
     *
     * @return array<string, WorkDay>
     */
    public function forEmployeeMonth(Employee $employee, int $year, int $month): array
    {
        $first = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC');
        $last = $first->copy()->endOfMonth();

        $calendar = $this->calendarFor($employee);
        $holidays = $this->holidaysBetween($employee, $calendar, $first->toDateString(), $last->toDateString());
        $rows = $calendar === null
            ? collect()
            : CalendarAttendance::query()
                ->where('calendar_id', $calendar->id)
                ->whereNull('display_type')
                ->whereNull('resource_id')
                ->get();

        $workDays = [];

        for ($day = $first->copy(); $day->lessThanOrEqualTo($last); $day->addDay()) {
            $dateString = $day->toDateString();

            $workDays[$dateString] = $this->buildWorkDay(
                $calendar,
                $holidays->first(fn (CalendarLeave $holiday): bool => $this->holidayCovers($holiday, $dateString)),
                $calendar === null ? collect() : $this->filterRowsForDay($rows, $calendar, $day),
            );
        }

        return $workDays;
    }

    /**
     * The employee's own calendar, else the lowest-id active calendar of the employee's company,
     * else the lowest-id active calendar without a company; null means the employee has no schedule.
     */
    public function calendarFor(Employee $employee): ?Calendar
    {
        if ($employee->calendar_id !== null) {
            $own = Calendar::query()->find($employee->calendar_id);

            if ($own !== null) {
                return $own;
            }
        }

        if ($employee->company_id !== null) {
            $companyCalendar = Calendar::query()
                ->where('company_id', $employee->company_id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();

            if ($companyCalendar !== null) {
                return $companyCalendar;
            }
        }

        return Calendar::query()
            ->whereNull('company_id')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * Id of the employee's approved Time Off request covering the local date, or null (also when
     * Time Off is not installed).
     */
    public function approvedLeaveId(Employee $employee, CarbonInterface $date): ?int
    {
        if (! Schema::hasTable('time_off_leaves')) {
            return null;
        }

        $dateString = $date->toDateString();

        $leaveId = DB::table('time_off_leaves')
            ->where('employee_id', $employee->id)
            ->whereIn('state', ['validate_one', 'validate_two'])
            ->where('date_from', '<=', $dateString.' 23:59:59')
            ->where('date_to', '>=', $dateString.' 00:00:00')
            ->orderBy('id')
            ->value('id');

        return $leaveId === null ? null : (int) $leaveId;
    }

    /**
     * Public holiday covering the date. `calendar_leaves` dates are strings holding local wall-clock
     * values, either `Y-m-d` or `Y-m-d H:i:s`, so they are compared as strings.
     */
    protected function holidayFor(Employee $employee, ?Calendar $calendar, string $dateString): ?CalendarLeave
    {
        return $this->holidayQuery($employee, $calendar, $dateString, $dateString)->first();
    }

    /**
     * Holidays that overlap any date of the range, oldest first, so the first one covering a date is the
     * same row `holidayFor` returns for it.
     *
     * @return Collection<int, CalendarLeave>
     */
    protected function holidaysBetween(Employee $employee, ?Calendar $calendar, string $fromDate, string $toDate): Collection
    {
        return $this->holidayQuery($employee, $calendar, $fromDate, $toDate)->get();
    }

    /**
     * @return Builder<CalendarLeave>
     */
    protected function holidayQuery(Employee $employee, ?Calendar $calendar, string $fromDate, string $toDate): Builder
    {
        return CalendarLeave::query()
            ->where('time_type', 'leave')
            ->whereNull('resource_id')
            ->where('date_from', '<=', $toDate.' 23:59:59')
            ->where('date_to', '>=', $fromDate)
            ->where(function ($query) use ($calendar): void {
                $query->whereNull('calendar_id');

                if ($calendar !== null) {
                    $query->orWhere('calendar_id', $calendar->id);
                }
            })
            ->where(function ($query) use ($employee): void {
                $query->whereNull('company_id');

                if ($employee->company_id !== null) {
                    $query->orWhere('company_id', $employee->company_id);
                }
            })
            ->orderBy('id');
    }

    /**
     * The same string comparison the holiday query makes, for holidays already in memory.
     */
    protected function holidayCovers(CalendarLeave $holiday, string $dateString): bool
    {
        return strcmp((string) $holiday->date_from, $dateString.' 23:59:59') <= 0
            && strcmp((string) $holiday->date_to, $dateString) >= 0;
    }

    /**
     * The single place a `WorkDay` is built, from the calendar (null = no schedule), the holiday covering
     * the date and the calendar rows of the date's weekday.
     *
     * @param  Collection<int, CalendarAttendance>  $rows
     */
    protected function buildWorkDay(?Calendar $calendar, ?CalendarLeave $holiday, Collection $rows): WorkDay
    {
        if ($calendar === null) {
            return new WorkDay(
                isWorkingDay: false,
                hasSchedule: false,
                startMinute: null,
                endMinute: null,
                expectedMinutes: 0,
                lunch: [],
                holidayName: $holiday?->name,
                isHoliday: $holiday !== null,
                isRestDay: false,
            );
        }

        $counted = $rows->reject(fn (CalendarAttendance $row): bool => $row->day_period === 'lunch');
        $lunch = $rows
            ->filter(fn (CalendarAttendance $row): bool => $row->day_period === 'lunch')
            ->map(fn (CalendarAttendance $row): array => [
                'from' => $this->toMinute($row->hour_from),
                'to'   => $this->toMinute($row->hour_to),
            ])
            ->sortBy('from')
            ->values()
            ->all();

        $isRestDay = $counted->isEmpty();
        $isWorkingDay = ! $isRestDay && $holiday === null;

        return new WorkDay(
            isWorkingDay: $isWorkingDay,
            hasSchedule: true,
            startMinute: $isRestDay ? null : $counted->min(fn (CalendarAttendance $row): int => $this->toMinute($row->hour_from)),
            endMinute: $isRestDay ? null : $counted->max(fn (CalendarAttendance $row): int => $this->toMinute($row->hour_to)),
            expectedMinutes: $isWorkingDay
                ? (int) $counted->sum(fn (CalendarAttendance $row): int => max(0, $this->toMinute($row->hour_to) - $this->toMinute($row->hour_from)))
                : 0,
            lunch: $lunch,
            holidayName: $holiday?->name,
            isHoliday: $holiday !== null,
            isRestDay: $isRestDay,
        );
    }

    /**
     * In-memory twin of the `rowsFor` query: the calendar's rows of the day's weekday and, on two-week
     * calendars, of the day's week type.
     *
     * @param  Collection<int, CalendarAttendance>  $rows
     * @return Collection<int, CalendarAttendance>
     */
    protected function filterRowsForDay(Collection $rows, Calendar $calendar, Carbon $day): Collection
    {
        $weekday = strtolower($day->englishDayOfWeek);
        $weekType = (string) CalendarAttendance::getWeekType($day);

        return $rows
            ->filter(fn (CalendarAttendance $row): bool => strtolower((string) $row->day_of_week) === $weekday
                && (! $calendar->two_weeks_calendar || $row->week_type === null || (string) $row->week_type === $weekType))
            ->values();
    }

    /**
     * Calendar rows of the date's weekday (and week type on two-week calendars); section lines and
     * resource-specific rows are not part of the shared schedule.
     *
     * @return Collection<int, CalendarAttendance>
     */
    protected function rowsFor(Calendar $calendar, string $dateString): Collection
    {
        $day = Carbon::createFromFormat('Y-m-d', $dateString, 'UTC')->startOfDay();

        $query = CalendarAttendance::query()
            ->where('calendar_id', $calendar->id)
            ->where('day_of_week', strtolower($day->englishDayOfWeek))
            ->whereNull('display_type')
            ->whereNull('resource_id');

        if ($calendar->two_weeks_calendar) {
            $weekType = (string) CalendarAttendance::getWeekType($day);

            $query->where(function ($weekQuery) use ($weekType): void {
                $weekQuery->whereNull('week_type')->orWhere('week_type', $weekType);
            });
        }

        return $query->get();
    }

    /**
     * Decimal hours ("8", "13.5") to minutes from midnight.
     */
    protected function toMinute(mixed $hours): int
    {
        return (int) round(((float) $hours) * 60);
    }
}
