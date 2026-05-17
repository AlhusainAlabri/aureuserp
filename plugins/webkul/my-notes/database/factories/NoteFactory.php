<?php

namespace Webkul\MyNotes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\MyNotes\Models\Note;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'ulid'        => (string) Str::ulid(),
            'type'        => fake()->randomElement(['text', 'checklist', 'reminder', 'voice']),
            'title'       => fake()->optional(0.7)->sentence(3),
            'body'        => fake()->optional(0.8)->paragraph(),
            'color'       => fake()->randomElement(['default', 'red', 'orange', 'yellow', 'green', 'teal', 'blue', 'purple', 'pink', 'gray']),
            'tags'        => fake()->optional(0.5)->randomElements(['work', 'personal', 'urgent', 'idea', 'meeting'], fake()->numberBetween(1, 3)),
            'is_pinned'   => fake()->boolean(10),
            'is_archived' => fake()->boolean(5),
            'sort_order'  => fake()->numberBetween(0, 100),
            'user_id'     => User::factory(),
            'company_id'  => Company::factory(),
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'text']);
    }

    public function checklist(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'checklist']);
    }

    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'        => 'reminder',
            'reminder_at' => now()->addDay(),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => ['is_pinned' => true]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['is_archived' => true]);
    }
}
