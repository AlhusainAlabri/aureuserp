<?php

namespace Webkul\Meetings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingAttachment;
use Webkul\Security\Models\User;

/**
 * @extends Factory<MeetingAttachment>
 */
class MeetingAttachmentFactory extends Factory
{
    protected $model = MeetingAttachment::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'file_path'  => 'meetings/'.now()->year.'/'.fake()->uuid().'.pdf',
            'file_name'  => fake()->word().'.pdf',
            'file_size'  => fake()->numberBetween(1000, 100000),
            'mime_type'  => 'application/pdf',
            'creator_id' => User::query()->value('id') ?? User::factory(),
        ];
    }
}
