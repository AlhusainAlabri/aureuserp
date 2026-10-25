<?php

namespace Webkul\Attendance\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Employee\Models\Employee;

class PunchRecorder
{
    /**
     * Punch attributes a caller may pass through `$meta`.
     */
    public const META_ATTRIBUTES = ['device_ref', 'import_batch_id', 'ip', 'latitude', 'longitude', 'creator_id'];

    public function __construct(protected AttendanceDayCalculator $calculator) {}

    /**
     * Single entry point for every punch source: stores the punch (or returns the identical one already
     * stored for the employee, instant to the second and source) and recalculates its local day.
     *
     * @param  array{device_ref?: ?string, import_batch_id?: ?int, ip?: ?string, latitude?: float|string|null, longitude?: float|string|null, creator_id?: ?int}  $meta
     */
    public function record(
        Employee $employee,
        CarbonInterface $at,
        PunchSource $source,
        ?PunchDirection $direction = null,
        array $meta = [],
    ): AttendancePunch {
        return DB::transaction(function () use ($employee, $at, $source, $direction, $meta): AttendancePunch {
            $punch = $this->store($employee, $at, $source, $direction, $meta);

            $this->calculator->calculate($employee, $this->localMoment($employee, $at));

            return $punch;
        });
    }

    /**
     * Stores the punch like `record()` (same lookup, same duplicate handling, `wasRecentlyCreated` tells a
     * new punch from the identical one already stored) but leaves the day alone. Bulk callers use it and
     * then recalculate each affected day once; everyone else wants `record()`.
     *
     * @param  array{device_ref?: ?string, import_batch_id?: ?int, ip?: ?string, latitude?: float|string|null, longitude?: float|string|null, creator_id?: ?int}  $meta
     */
    public function store(
        Employee $employee,
        CarbonInterface $at,
        PunchSource $source,
        ?PunchDirection $direction = null,
        array $meta = [],
    ): AttendancePunch {
        // The column holds whole seconds; truncate so the lookup and the insert agree.
        $instant = Carbon::instance($at)->utc()->startOfSecond();

        return AttendancePunch::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'punched_at'  => $instant->format('Y-m-d H:i:s'),
                'source'      => $source,
            ],
            array_merge(
                Arr::only($meta, self::META_ATTRIBUTES),
                ['direction' => $direction],
            ),
        );
    }

    /**
     * The instant in the employee's zone, which decides the attendance day the punch belongs to.
     */
    public function localMoment(Employee $employee, CarbonInterface $at): Carbon
    {
        return Carbon::instance($at)->utc()->startOfSecond()->setTimezone($employee->time_zone ?: config('app.timezone'));
    }
}
