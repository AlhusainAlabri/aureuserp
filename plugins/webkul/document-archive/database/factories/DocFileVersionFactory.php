<?php

namespace Webkul\DocumentArchive\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFileVersion;
use Webkul\Security\Models\User;

/**
 * @extends Factory<DocFileVersion>
 */
class DocFileVersionFactory extends Factory
{
    protected $model = DocFileVersion::class;

    public function definition(): array
    {
        return [
            'file_id'           => DocFile::query()->value('id') ?? DocFile::factory(),
            'version_number'    => 1,
            'file_path'         => 'documents/'.now()->year.'/versions/'.fake()->uuid().'.bin',
            'file_size'         => fake()->numberBetween(1024, 5_000_000),
            'original_filename' => fake()->slug(3).'.pdf',
            'change_note'       => fake()->sentence(),
            'creator_id'        => User::query()->value('id') ?? User::factory(),
        ];
    }
}
