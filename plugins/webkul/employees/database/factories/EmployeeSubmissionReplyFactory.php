<?php

namespace Webkul\Employee\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Employee\Models\EmployeeSubmissionReply;
use Webkul\Security\Models\User;

class EmployeeSubmissionReplyFactory extends Factory
{
    protected $model = EmployeeSubmissionReply::class;

    public function definition(): array
    {
        return [
            'submission_id' => EmployeeSubmission::factory(),
            'body'          => $this->faker->paragraph(),
            'is_internal'   => $this->faker->boolean(20),
            'replied_by'    => User::query()->value('id') ?? User::factory(),
        ];
    }
}
