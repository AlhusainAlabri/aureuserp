<?php

namespace Webkul\Assets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Assets\Enums\AssetCategory;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Models\Asset;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'name'           => fake()->words(3, true),
            'description'    => fake()->sentence(),
            'category'       => fake()->randomElement(AssetCategory::cases())->value,
            'serial_number'  => fake()->unique()->bothify('SN-####-????'),
            'status'         => AssetStatus::Available,
            'value'          => fake()->randomFloat(3, 50, 5000),
            'location'       => fake()->city(),
            'purchased_at'   => fake()->dateTimeBetween('-3 years', 'now'),
            'notes'          => fake()->optional()->sentence(),
            'company_id'     => Company::query()->value('id') ?? Company::factory(),
            'creator_id'     => User::query()->value('id') ?? User::factory(),
        ];
    }

    public function borrowed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AssetStatus::Borrowed,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AssetStatus::Maintenance,
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AssetStatus::Retired,
        ]);
    }
}
