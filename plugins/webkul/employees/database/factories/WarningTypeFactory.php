<?php

namespace Webkul\Employee\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Employee\Models\WarningType;
use Webkul\Security\Models\User;

class WarningTypeFactory extends Factory
{
    protected $model = WarningType::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'sort'        => $this->faker->numberBetween(1, 100),
            'creator_id'  => User::query()->value('id') ?? User::factory(),
        ];
    }
}
