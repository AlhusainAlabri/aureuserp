<?php

namespace Webkul\Employee\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeSubmission;

class EmployeeSubmissionFactory extends Factory
{
    protected $model = EmployeeSubmission::class;

    public function definition(): array
    {
        return [
            'type'           => $this->faker->randomElement(['complaint', 'suggestion', 'inquiry', 'feedback']),
            'subject'        => $this->faker->sentence(),
            'body'           => $this->faker->paragraph(3),
            'employee_id'    => Employee::factory(),
            'submitter_name' => fn (array $attributes) => Employee::find($attributes['employee_id'])?->name ?? $this->faker->name(),
            'status'         => $this->faker->randomElement(['open', 'under_review', 'resolved', 'closed']),
            'priority'       => $this->faker->randomElement(['low', 'medium', 'high']),
            'attachments'    => null,
        ];
    }
}
