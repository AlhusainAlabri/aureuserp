<?php

namespace Webkul\Correspondence\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\Correspondence\Models\Department;
use Webkul\Employee\Models\Department as EmployeeDepartment;
use Webkul\Support\Models\Company;

class CorrespondenceDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        $companyId = Company::query()->value('id');

        if (! $companyId) {
            return;
        }

        if (Schema::hasTable('employees_departments')) {
            EmployeeDepartment::query()
                ->orderBy('id')
                ->each(function (EmployeeDepartment $employeeDepartment) use ($companyId): void {
                    $code = strtoupper(Str::limit(Str::slug($employeeDepartment->name, ''), 4, ''));

                    if ($code === '') {
                        $code = 'D'.str_pad((string) $employeeDepartment->id, 3, '0', STR_PAD_LEFT);
                    }

                    Department::query()->updateOrCreate(
                        ['employees_department_id' => $employeeDepartment->id],
                        [
                            'name'       => $employeeDepartment->name,
                            'code'       => Str::limit($code, 4, ''),
                            'manager_id' => $employeeDepartment->manager_id,
                            'company_id' => $employeeDepartment->company_id ?? $companyId,
                        ],
                    );
                });

            return;
        }

        if (Department::query()->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Administration', 'code' => 'ADMN'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Projects', 'code' => 'PROJ'],
        ];

        foreach ($defaults as $department) {
            Department::query()->firstOrCreate(
                ['code' => $department['code']],
                [
                    'name'       => $department['name'],
                    'company_id' => $companyId,
                ],
            );
        }
    }
}
