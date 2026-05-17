<?php

namespace Webkul\DocumentArchive\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocShareLink;
use Webkul\Security\Models\User;

/**
 * @extends Factory<DocShareLink>
 */
class DocShareLinkFactory extends Factory
{
    protected $model = DocShareLink::class;

    public function definition(): array
    {
        return [
            'file_id'           => DocFile::query()->value('id') ?? DocFile::factory(),
            'token'             => Str::random(64),
            'shared_by'         => User::query()->value('id') ?? User::factory(),
            'shared_with_email' => fake()->safeEmail(),
            'view_once'         => false,
            'expires_at'        => now()->addDays(7),
            'viewed_at'         => null,
            'view_count'        => 0,
            'is_active'         => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function viewOnce(): static
    {
        return $this->state(fn (array $attributes): array => [
            'view_once' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
