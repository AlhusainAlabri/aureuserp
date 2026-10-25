<?php

namespace Webkul\Attendance\Console;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Services\AttendanceDayCalculator;
use Webkul\Employee\Models\Employee;

class CloseDayCommand extends Command
{
    protected $signature = 'attendance:close-day {--date= : Local date to close (Y-m-d); defaults to yesterday}';

    protected $description = 'Calculate the attendance day of every active employee and record absences';

    public function handle(AttendanceDayCalculator $calculator): int
    {
        $date = $this->closingDate();

        if ($date === null) {
            $this->error(__('attendances::attendances.console.close_day.invalid_date'));

            return self::FAILURE;
        }

        $dateString = $date->toDateString();
        $processed = 0;
        $absent = 0;
        $failed = 0;

        Employee::query()
            ->where('is_active', true)
            ->where(function ($query) use ($dateString): void {
                $query->whereNull('departure_date')->orWhere('departure_date', '>=', $dateString);
            })
            ->chunkById(200, function ($employees) use ($calculator, $date, $dateString, &$processed, &$absent, &$failed): void {
                foreach ($employees as $employee) {
                    $processed++;

                    try {
                        if ($this->close($calculator, $employee, $date, $dateString)) {
                            $absent++;
                        }
                    } catch (Throwable $exception) {
                        $failed++;
                        report($exception);
                        $this->warn(__('attendances::attendances.console.close_day.failed', [
                            'employee' => $employee->name,
                            'message'  => $exception->getMessage(),
                        ]));
                    }
                }
            });

        $this->info(__('attendances::attendances.console.close_day.summary', [
            'date'      => $dateString,
            'processed' => $processed,
            'absent'    => $absent,
        ]));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Calculates the employee's day; true when this run wrote a new absence.
     *
     * The calculator never stores an absence itself, it hands back an unsaved `absent` row for a
     * scheduled working day without punches, leave or holiday. A day that is not over yet in the
     * employee's own zone stays open and is not closed.
     */
    protected function close(AttendanceDayCalculator $calculator, Employee $employee, CarbonInterface $date, string $dateString): bool
    {
        $day = $calculator->calculate($employee, $date);

        if ($day->exists || $day->status !== DayStatus::Absent) {
            return false;
        }

        $zone = $employee->time_zone ?: config('app.timezone');

        if ($dateString >= Carbon::now($zone)->toDateString()) {
            return false;
        }

        $day->save();

        return $day->wasRecentlyCreated;
    }

    protected function closingDate(): ?CarbonInterface
    {
        $zone = config('app.timezone');
        $option = $this->option('date');

        if (blank($option)) {
            return Carbon::yesterday($zone);
        }

        if (! Carbon::canBeCreatedFromFormat((string) $option, 'Y-m-d')) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', (string) $option, $zone);

        return $date->format('Y-m-d') === $option ? $date->startOfDay() : null;
    }
}
