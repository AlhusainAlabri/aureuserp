<?php

namespace Webkul\Correspondence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Correspondence\Models\Department;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name'       => fake()->unique()->company().' Department',
            'code'       => strtoupper(fake()->unique()->bothify('??')),
            'manager_id' => User::query()->value('id') ?? User::factory(),
            'company_id' => Company::query()->value('id') ?? Company::factory(),
        ];
    }
}
