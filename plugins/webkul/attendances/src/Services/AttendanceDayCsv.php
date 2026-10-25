<?php

namespace Webkul\Attendance\Services;

use App\Support\Exports\Utf8Csv;
use Illuminate\Support\Carbon;
use Webkul\Attendance\Models\AttendanceDay;

/**
 * The attendance day list as a UTF-8 CSV (with BOM, so Excel reads Arabic): times in each employee's own
 * zone, worked time as h:mm and the rest in minutes.
 */
class AttendanceDayCsv
{
    /**
     * @param  iterable<AttendanceDay>  $days  employee relation loaded
     */
    public function build(iterable $days): string
    {
        $rows = [[
            __('attendances::attendances.day.columns.employee'),
            __('attendances::attendances.day.columns.date'),
            __('attendances::attendances.day.columns.first_in'),
            __('attendances::attendances.day.columns.last_out'),
            __('attendances::attendances.day.columns.worked'),
            __('attendances::attendances.day.columns.late'),
            __('attendances::attendances.day.columns.early_leave'),
            __('attendances::attendances.day.columns.overtime'),
            __('attendances::attendances.day.columns.status'),
        ]];

        foreach ($days as $day) {
            $zone = $day->employee?->time_zone ?: config('app.timezone');

            $rows[] = [
                $day->employee?->name,
                $day->date->format('Y-m-d'),
                $this->localTime($day->first_in, $zone),
                $this->localTime($day->last_out, $zone),
                self::formatMinutes((int) $day->worked_minutes),
                (int) $day->late_minutes,
                (int) $day->early_leave_minutes,
                (int) $day->overtime_minutes,
                $day->status->getLabel(),
            ];
        }

        return Utf8Csv::fromRows(array_map(
            fn (array $row): array => array_map(self::neutralise(...), $row),
            $rows,
        ));
    }

    public static function formatMinutes(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    protected function localTime(?Carbon $moment, string $zone): string
    {
        return $moment?->copy()->setTimezone($zone)->format('H:i') ?? '';
    }

    /**
     * A text cell starting with = + - @ (or a control character) would be run as a formula by spreadsheets.
     */
    public static function neutralise(mixed $cell): mixed
    {
        if (is_string($cell) && $cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$cell;
        }

        return $cell;
    }
}
