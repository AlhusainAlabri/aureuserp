<?php

namespace Webkul\Employee\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;

class WarningTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('employees_warning_types')->delete();

        $user = User::first();

        $types = [
            ['creator_id' => $user?->id, 'name' => 'Verbal Warning', 'description' => 'A verbal warning given to the employee.', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['creator_id' => $user?->id, 'name' => 'Written Warning', 'description' => 'A formal written warning documented in the employee file.', 'sort' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['creator_id' => $user?->id, 'name' => 'Final Warning', 'description' => 'A final warning before potential termination.', 'sort' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['creator_id' => $user?->id, 'name' => 'Performance Improvement', 'description' => 'Performance improvement plan notice.', 'sort' => 4, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('employees_warning_types')->insert($types);
    }
}
