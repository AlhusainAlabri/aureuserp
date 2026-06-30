<?php

namespace Database\Factories;

use App\Models\DashboardShortcut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardShortcut>
 */
class DashboardShortcutFactory extends Factory
{
    protected $model = DashboardShortcut::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_en'          => fake()->words(2, true),
            'title_ar'          => fake()->optional()->words(2, true),
            'url'               => 'https://example.com',
            'icon'              => 'heroicon-o-link',
            'color'             => 'info',
            'sort'              => fake()->numberBetween(0, 100),
            'is_active'         => true,
            'opens_in_new_tab'  => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
