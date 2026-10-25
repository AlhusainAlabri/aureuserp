<?php

namespace Webkul\Attendance\Settings;

use Spatie\LaravelSettings\Settings;

class AttendanceSettings extends Settings
{
    public int $grace_minutes;

    public float $overtime_multiplier_normal;

    public float $overtime_multiplier_rest;

    public float $overtime_multiplier_holiday;

    public int $minimum_overtime_minutes;

    public bool $allow_self_checkin;

    /**
     * IP addresses or CIDR ranges allowed to self check in; empty means any address.
     */
    public array $allowed_ip_ranges;

    /**
     * Optional circle with the keys latitude, longitude and radius_metres.
     */
    public ?array $geofence;

    public string $workday_close_time;

    public static function group(): string
    {
        return 'attendance';
    }
}
