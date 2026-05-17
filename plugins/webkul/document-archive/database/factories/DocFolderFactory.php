<?php

namespace Webkul\DocumentArchive\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<DocFolder>
 */
class DocFolderFactory extends Factory
{
    protected $model = DocFolder::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name'        => $name,
            'slug'        => Str::slug($name).'-'.Str::random(6),
            'description' => fake()->sentence(),
            'parent_id'   => null,
            'color'       => fake()->hexColor(),
            'icon'        => 'heroicon-o-folder',
            'is_private'  => false,
            'sort_order'  => 0,
            'company_id'  => Company::query()->value('id') ?? Company::factory(),
            'creator_id'  => User::query()->value('id') ?? User::factory(),
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes): array => ['is_private' => true]);
    }

    public function withPassword(string $password = 'secret'): static
    {
        return $this->state(fn (array $attributes): array => [
            'password_hash' => bcrypt($password),
        ]);
    }
}
