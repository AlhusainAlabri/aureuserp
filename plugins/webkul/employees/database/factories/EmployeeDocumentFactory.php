<?php

namespace Webkul\Employee\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeDocument;
use Webkul\Security\Models\User;

class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    public function definition(): array
    {
        return [
            'employee_id'   => Employee::factory(),
            'document_type' => $this->faker->randomElement(['id_card', 'passport', 'residence_permit', 'contract', 'certificate', 'other']),
            'document_name' => $this->faker->words(3, true),
            'file_path'     => 'employees/'.$this->faker->numberBetween(1, 100).'/documents/'.$this->faker->uuid().'.pdf',
            'expiry_date'   => $this->faker->optional()->dateTimeBetween('+1 day', '+2 years'),
            'notes'         => $this->faker->optional()->sentence(),
            'creator_id'    => User::query()->value('id') ?? User::factory(),
        ];
    }
}
