<?php

namespace Webkul\Attendance\Casts;

use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Datetime columns that hold UTC wall-clock values regardless of the application timezone.
 *
 * Reads return a UTC Carbon; writes convert any Carbon/DateTime to UTC and treat plain strings as UTC.
 *
 * @implements CastsAttributes<Carbon|null, DateTimeInterface|string|null>
 */
class UtcDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value, 'UTC')->setTimezone('UTC');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $moment = $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value, 'UTC');

        return $moment->setTimezone('UTC')->format('Y-m-d H:i:s');
    }
}
