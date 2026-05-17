<?php

namespace Webkul\Correspondence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Models\CorrespondenceAttachment;
use Webkul\Security\Models\User;

/**
 * @extends Factory<CorrespondenceAttachment>
 */
class CorrespondenceAttachmentFactory extends Factory
{
    protected $model = CorrespondenceAttachment::class;

    public function definition(): array
    {
        return [
            'correspondence_id' => Correspondence::factory(),
            'file_path'         => 'correspondence/'.now()->year.'/file.pdf',
            'file_name'         => 'file.pdf',
            'file_size'         => 1024,
            'mime_type'         => 'application/pdf',
            'creator_id'        => User::query()->value('id') ?? User::factory(),
        ];
    }
}
