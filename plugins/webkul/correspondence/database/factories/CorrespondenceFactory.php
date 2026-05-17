<?php

namespace Webkul\Correspondence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Models\Department;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<Correspondence>
 */
class CorrespondenceFactory extends Factory
{
    protected $model = Correspondence::class;

    public function definition(): array
    {
        $direction = fake()->randomElement(['outgoing', 'incoming']);

        return [
            'direction'           => $direction,
            'type'                => fake()->randomElement(['official', 'internal', 'external']),
            'priority'            => fake()->randomElement(['normal', 'urgent', 'confidential']),
            'subject'             => fake()->sentence(5),
            'body'                => fake()->paragraphs(2, true),
            'sender_name'         => fake()->name(),
            'sender_entity'       => fake()->company(),
            'from_department_id'  => Department::query()->value('id') ?? Department::factory(),
            'to_department_id'    => Department::query()->value('id') ?? Department::factory(),
            'to_user_id'          => User::query()->value('id') ?? User::factory(),
            'to_external_email'   => fake()->safeEmail(),
            'status'              => $direction === 'incoming' ? 'received' : 'draft',
            'received_at'         => $direction === 'incoming' ? now()->toDateString() : null,
            'sent_at'             => null,
            'due_date'            => now()->addDays(fake()->numberBetween(2, 20))->toDateString(),
            'project_id'          => Project::query()->value('id') ?? Project::factory(),
            'meeting_id'          => Meeting::query()->value('id'),
            'purchase_request_id' => null,
            'company_id'          => Company::query()->value('id') ?? Company::factory(),
            'creator_id'          => User::query()->value('id') ?? User::factory(),
        ];
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'direction'   => 'outgoing',
            'status'      => 'draft',
            'received_at' => null,
        ]);
    }

    public function incoming(): static
    {
        return $this->state(fn (array $attributes): array => [
            'direction'   => 'incoming',
            'status'      => 'received',
            'received_at' => now()->toDateString(),
        ]);
    }

    public function approved(): static
    {
        return $this->outgoing()->state(fn (array $attributes): array => ['status' => 'approved']);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => now()->subDay()->toDateString(),
            'status'   => 'received',
        ]);
    }
}
