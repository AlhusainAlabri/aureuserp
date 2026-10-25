<?php

namespace Webkul\Attendance\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\IpUtils;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Employee\Models\Employee;

class SelfCheckInService
{
    /**
     * Minimum number of seconds between two punches of the same employee.
     */
    public const MINIMUM_SECONDS_BETWEEN_PUNCHES = 60;

    public const EARTH_RADIUS_METRES = 6371000;

    public function __construct(
        protected PunchRecorder $recorder,
        protected AttendanceSettings $settings,
    ) {}

    /**
     * Records the signed-in employee's own punch, alternating in and out through the local day.
     *
     * @throws AttendanceException When the punch is not allowed; the message is translated.
     */
    public function punch(Employee $employee, ?string $ip, ?float $latitude, ?float $longitude): AttendancePunch
    {
        if (! $this->settings->allow_self_checkin) {
            throw new AttendanceException(__('attendances::attendances.self_check_in.disabled'));
        }

        $this->assertAddressAllowed($ip);
        $this->assertInsideGeofence($latitude, $longitude);

        return DB::transaction(function () use ($employee, $ip, $latitude, $longitude): AttendancePunch {
            // Serialise the employee's own taps so two quick requests cannot both pass the 60 second guard.
            Employee::query()->whereKey($employee->getKey())->lockForUpdate()->first();

            $now = Carbon::now('UTC');

            $this->assertNotTooSoon($employee, $now);

            return $this->recorder->record($employee, $now, PunchSource::Self, $this->nextDirection($employee, $now), [
                'ip'        => $ip,
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ]);
        });
    }

    /**
     * The direction the employee's next self punch will have: out after an "in" today, otherwise in.
     */
    public function nextDirection(Employee $employee, ?Carbon $at = null): PunchDirection
    {
        $zone = $employee->time_zone ?: config('app.timezone');
        $localNow = ($at ?? Carbon::now('UTC'))->copy()->setTimezone($zone);
        $start = $localNow->copy()->startOfDay()->utc();
        $end = $localNow->copy()->addDay()->startOfDay()->utc();

        $punches = AttendancePunch::query()
            ->where('employee_id', $employee->getKey())
            ->where('punched_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('punched_at', '<', $end->format('Y-m-d H:i:s'))
            ->orderBy('punched_at')
            ->orderBy('id')
            ->get(['id', 'direction', 'punched_at']);

        $last = $punches->last();

        if ($last === null) {
            return PunchDirection::In;
        }

        $lastWasIn = $last->direction !== null
            ? $last->direction === PunchDirection::In
            : $punches->count() % 2 === 1;

        return $lastWasIn ? PunchDirection::Out : PunchDirection::In;
    }

    protected function assertAddressAllowed(?string $ip): void
    {
        $ranges = array_values(array_filter(
            (array) $this->settings->allowed_ip_ranges,
            fn (mixed $range): bool => is_string($range) && trim($range) !== '',
        ));

        if ($ranges === []) {
            return;
        }

        if ($ip === null || trim($ip) === '' || ! IpUtils::checkIp(trim($ip), array_map('trim', $ranges))) {
            throw new AttendanceException(__('attendances::attendances.self_check_in.ip_not_allowed'));
        }
    }

    protected function assertInsideGeofence(?float $latitude, ?float $longitude): void
    {
        $geofence = $this->settings->geofence;

        if (empty($geofence)) {
            return;
        }

        if ($latitude === null || $longitude === null || abs($latitude) > 90 || abs($longitude) > 180) {
            throw new AttendanceException(__('attendances::attendances.self_check_in.location_required'));
        }

        $distance = $this->distanceInMetres(
            (float) $geofence['latitude'],
            (float) $geofence['longitude'],
            $latitude,
            $longitude,
        );

        // Rounded to the millimetre so a point exactly on the radius is not lost to float noise.
        if (round($distance, 3) > (float) $geofence['radius_metres']) {
            throw new AttendanceException(__('attendances::attendances.self_check_in.outside_geofence'));
        }
    }

    protected function assertNotTooSoon(Employee $employee, Carbon $now): void
    {
        $latest = AttendancePunch::query()
            ->where('employee_id', $employee->getKey())
            ->orderByDesc('punched_at')
            ->first(['id', 'punched_at']);

        if ($latest !== null && $latest->punched_at->greaterThan($now->copy()->subSeconds(self::MINIMUM_SECONDS_BETWEEN_PUNCHES))) {
            throw new AttendanceException(__('attendances::attendances.self_check_in.too_soon'));
        }
    }

    /**
     * Great-circle distance between two coordinates by the haversine formula.
     */
    protected function distanceInMetres(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $fromLat = deg2rad($fromLatitude);
        $toLat = deg2rad($toLatitude);
        $deltaLat = $toLat - $fromLat;
        $deltaLng = deg2rad($toLongitude - $fromLongitude);

        $a = sin($deltaLat / 2) ** 2 + cos($fromLat) * cos($toLat) * sin($deltaLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METRES * asin(min(1.0, sqrt($a)));
    }
}
