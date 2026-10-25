<?php

namespace Webkul\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Employee\Models\Employee;

/**
 * @extends Factory<AttendancePunch>
 */
class AttendancePunchFactory extends Factory
{
    protected $model = AttendancePunch::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'punched_at'  => fake()->dateTimeBetween('-1 month', 'now', 'UTC'),
            'source'      => PunchSource::Manual,
            'direction'   => null,
            'creator_id'  => null,
        ];
    }
}
