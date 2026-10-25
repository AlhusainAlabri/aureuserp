<?php

namespace Webkul\Payroll\Settings;

use Spatie\LaravelSettings\Settings;

class GratuitySettings extends Settings
{
    public float $minimum_service_years;

    public string $eligible_category;

    public int $leave_payout_divisor;

    public static function group(): string
    {
        return 'gratuity';
    }
}
