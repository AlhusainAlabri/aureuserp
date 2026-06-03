<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Models\PayslipLine;
use Webkul\Payroll\Models\SalaryComponent;

/**
 * @extends Factory<PayslipLine>
 */
class PayslipLineFactory extends Factory
{
    protected $model = PayslipLine::class;

    public function definition(): array
    {
        return [
            'payslip_id'   => Payslip::factory(),
            'component_id' => SalaryComponent::factory(),
            'type'         => SalaryComponentType::Earning,
            'code'         => strtoupper(fake()->lexify('???')),
            'name'         => fake()->words(2, true),
            'quantity'     => 1,
            'rate'         => fake()->randomFloat(3, 100, 2000),
            'amount'       => fake()->randomFloat(3, 100, 2000),
            'sort_order'   => fake()->numberBetween(10, 100),
        ];
    }
}
