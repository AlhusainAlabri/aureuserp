<?php

namespace Webkul\Employee\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeWarning;
use Webkul\Employee\Models\WarningType;
use Webkul\Security\Models\User;

class EmployeeWarningFactory extends Factory
{
    protected $model = EmployeeWarning::class;

    public function definition(): array
    {
        return [
            'employee_id'      => Employee::factory(),
            'warning_type_id'  => WarningType::factory(),
            'subject'          => $this->faker->sentence(),
            'description'      => $this->faker->paragraph(),
            'issued_at'        => $this->faker->dateTimeBetween('-1 year', 'now'),
            'effective_date'   => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'expiry_date'      => $this->faker->optional()->dateTimeBetween('+1 month', '+1 year'),
            'is_acknowledged'  => $this->faker->boolean(20),
            'acknowledged_at'  => fn (array $attributes) => $attributes['is_acknowledged'] ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'acknowledged_by'  => fn (array $attributes) => $attributes['is_acknowledged'] ? (User::query()->value('id') ?? User::factory()) : null,
            'creator_id'       => User::query()->value('id') ?? User::factory(),
        ];
    }
}
