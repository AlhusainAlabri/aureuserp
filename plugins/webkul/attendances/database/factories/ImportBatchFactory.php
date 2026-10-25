<?php

namespace Webkul\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attendance\Models\ImportBatch;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'file_name'            => fake()->word().'.csv',
            'total_rows'           => 0,
            'matched_rows'         => 0,
            'duplicate_rows'       => 0,
            'invalid_rows'         => 0,
            'unmatched_device_ids' => [],
            'created_by'           => null,
        ];
    }
}
