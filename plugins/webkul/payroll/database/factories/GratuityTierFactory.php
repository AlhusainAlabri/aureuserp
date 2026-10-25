<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Payroll\Models\GratuityTier;

/**
 * @extends Factory<GratuityTier>
 */
class GratuityTierFactory extends Factory
{
    protected $model = GratuityTier::class;

    public function definition(): array
    {
        return [
            'from_year'      => 0,
            'to_year'        => null,
            'days_per_year'  => 30,
            'effective_from' => '2030-01-01',
            'effective_to'   => null,
            'creator_id'     => null,
        ];
    }
}
