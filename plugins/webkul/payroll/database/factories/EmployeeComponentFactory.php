<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\SalaryComponent;

/**
 * @extends Factory<EmployeeComponent>
 */
class EmployeeComponentFactory extends Factory
{
    protected $model = EmployeeComponent::class;

    public function definition(): array
    {
        return [
            'employee_id'  => Employee::factory(),
            'component_id' => SalaryComponent::factory(),
            'amount'       => fake()->randomFloat(3, 100, 3000),
            'percent'      => null,
            'start_date'   => now()->startOfYear(),
            'end_date'     => null,
            'notes'        => fake()->optional()->sentence(),
            'creator_id'   => null,
        ];
    }
}
