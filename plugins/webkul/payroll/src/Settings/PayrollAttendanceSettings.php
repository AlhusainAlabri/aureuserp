<?php

namespace Webkul\Payroll\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * What payroll takes from attendance. Working days follow the employee's calendar whenever the attendance plugin
 * is installed and the employee has a schedule; these switches only decide what is deducted or paid.
 */
class PayrollAttendanceSettings extends Settings
{
    public bool $deduct_absence;

    public bool $deduct_lateness;

    public bool $pay_overtime;

    public static function group(): string
    {
        return 'payroll_attendance';
    }
}
