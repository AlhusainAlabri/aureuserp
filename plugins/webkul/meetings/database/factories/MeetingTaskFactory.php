<?php

namespace Webkul\Meetings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Security\Models\User;

/**
 * @extends Factory<MeetingTask>
 */
class MeetingTaskFactory extends Factory
{
    protected $model = MeetingTask::class;

    public function definition(): array
    {
        return [
            'meeting_id'          => Meeting::factory(),
            'title'               => fake()->sentence(4),
            'description'         => fake()->optional()->paragraph(),
            'assigned_to'         => User::query()->value('id') ?? User::factory(),
            'due_date'            => now()->addDays(fake()->numberBetween(1, 14))->toDateString(),
            'status'              => 'pending',
            'priority'            => fake()->randomElement(['low', 'medium', 'high']),
            'purchase_request_id' => null,
            'completed_at'        => null,
            'creator_id'          => User::query()->value('id') ?? User::factory(),
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDay()->toDateString(),
            'status'   => 'pending',
        ]);
    }
}
