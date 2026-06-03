<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<SalaryComponent>
 */
class SalaryComponentFactory extends Factory
{
    protected $model = SalaryComponent::class;

    public function definition(): array
    {
        return [
            'code'             => strtoupper(fake()->unique()->lexify('???')),
            'name'             => fake()->words(2, true),
            'name_ar'          => 'مكون '.$this->faker->word(),
            'type'             => fake()->randomElement(SalaryComponentType::cases()),
            'calculation_type' => fake()->randomElement(CalculationType::cases()),
            'default_amount'   => fake()->randomFloat(3, 50, 2000),
            'default_percent'  => fake()->randomFloat(2, 1, 25),
            'formula'          => null,
            'is_taxable'       => fake()->boolean(),
            'is_active'        => true,
            'sort_order'       => fake()->numberBetween(10, 900),
            'account_id'       => null,
            'company_id'       => Company::query()->value('id'),
            'creator_id'       => null,
        ];
    }

    public function earning(): static
    {
        return $this->state(fn (): array => ['type' => SalaryComponentType::Earning]);
    }

    public function deduction(): static
    {
        return $this->state(fn (): array => ['type' => SalaryComponentType::Deduction]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
