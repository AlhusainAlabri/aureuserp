<?php

namespace Webkul\Meetings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'title'            => fake()->sentence(4),
            'type'             => fake()->randomElement(['internal', 'external', 'emergency', 'board']),
            'status'           => 'draft',
            'location'         => fake()->city(),
            'meeting_date'     => now()->addDays(fake()->numberBetween(1, 20)),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'project_id'       => Project::query()->value('id') ?? null,
            'company_id'       => Company::query()->value('id') ?? Company::factory(),
            'chair_person_id'  => User::query()->value('id') ?? User::factory(),
            'secretary_id'     => null,
            'agenda'           => fake()->paragraph(),
            'notes'            => fake()->paragraph(),
            'creator_id'       => User::query()->value('id') ?? User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'approved']);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'confirmed']);
    }
}
