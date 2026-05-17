<?php

namespace Webkul\Correspondence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Models\CorrespondenceFollower;
use Webkul\Security\Models\User;

/**
 * @extends Factory<CorrespondenceFollower>
 */
class CorrespondenceFollowerFactory extends Factory
{
    protected $model = CorrespondenceFollower::class;

    public function definition(): array
    {
        return [
            'correspondence_id' => Correspondence::factory(),
            'user_id'           => User::query()->value('id') ?? User::factory(),
        ];
    }
}
