<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Models\PasiRule;

/**
 * @extends Factory<PasiRule>
 */
class PasiRuleFactory extends Factory
{
    protected $model = PasiRule::class;

    public function definition(): array
    {
        return [
            'category'       => PasiCategory::Omani,
            'employee_rate'  => 7.00,
            'employer_rate'  => 11.50,
            'salary_cap'     => null,
            'effective_from' => '2030-01-01',
            'effective_to'   => null,
            'creator_id'     => null,
        ];
    }
}
