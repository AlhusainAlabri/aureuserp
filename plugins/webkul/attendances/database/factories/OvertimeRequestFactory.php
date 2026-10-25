<?php

namespace Webkul\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Employee\Models\Employee;

/**
 * @extends Factory<OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    protected $model = OvertimeRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date'        => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'minutes'     => 60,
            'day_type'    => OvertimeDayType::Normal,
            'multiplier'  => 1.25,
            'status'      => OvertimeStatus::Pending,
        ];
    }
}
