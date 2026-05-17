<?php

namespace Webkul\DocumentArchive\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<DocFile>
 */
class DocFileFactory extends Factory
{
    protected $model = DocFile::class;

    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'docx', 'xlsx', 'png', 'jpg', 'txt']);
        $original = fake()->slug(3).'.'.$extension;

        return [
            'folder_id'         => DocFolder::query()->value('id') ?? DocFolder::factory(),
            'name'              => fake()->words(3, true),
            'original_filename' => $original,
            'file_path'         => 'documents/'.now()->year.'/'.fake()->uuid().'.'.$extension,
            'file_size'         => fake()->numberBetween(1024, 5_000_000),
            'mime_type'         => static::mimeForExtension($extension),
            'extension'         => $extension,
            'description'       => fake()->sentence(),
            'tags'              => fake()->randomElements(['draft', 'final', 'confidential', 'shared', 'archived'], 2),
            'tag_color'         => fake()->hexColor(),
            'is_private'        => false,
            'expiry_date'       => null,
            'version'           => 1,
            'view_count'        => 0,
            'download_count'    => 0,
            'company_id'        => Company::query()->value('id') ?? Company::factory(),
            'creator_id'        => User::query()->value('id') ?? User::factory(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expiry_date' => now()->subDay()->toDateString(),
        ]);
    }

    public function expiringSoon(int $days = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'expiry_date' => now()->addDays($days)->toDateString(),
        ]);
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

    protected static function mimeForExtension(string $ext): string
    {
        return match (strtolower($ext)) {
            'pdf'         => 'application/pdf',
            'docx'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'txt'         => 'text/plain',
            default       => 'application/octet-stream',
        };
    }
}
