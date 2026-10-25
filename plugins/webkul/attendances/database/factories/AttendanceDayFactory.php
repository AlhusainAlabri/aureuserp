<?php

namespace Webkul\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Employee\Models\Employee;

/**
 * @extends Factory<AttendanceDay>
 */
class AttendanceDayFactory extends Factory
{
    protected $model = AttendanceDay::class;

    public function definition(): array
    {
        return [
            'employee_id'         => Employee::factory(),
            'date'                => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'first_in'            => null,
            'last_out'            => null,
            'worked_minutes'      => 0,
            'expected_minutes'    => 0,
            'late_minutes'        => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes'    => 0,
            'status'              => DayStatus::Present,
            'is_manual_override'  => false,
        ];
    }
}
