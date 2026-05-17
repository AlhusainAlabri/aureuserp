<?php

namespace Webkul\Meetings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingAttendee;
use Webkul\Security\Models\User;

/**
 * @extends Factory<MeetingAttendee>
 */
class MeetingAttendeeFactory extends Factory
{
    protected $model = MeetingAttendee::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'user_id'    => User::query()->value('id') ?? User::factory(),
            'attended'   => false,
            'role'       => 'member',
            'signed_at'  => null,
        ];
    }
}
