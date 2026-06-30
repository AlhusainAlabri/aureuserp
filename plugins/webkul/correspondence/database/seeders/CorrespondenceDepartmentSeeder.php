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
    /** @var array<int, string> */
    protected array $usedCodes = [];

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
                    Department::query()->updateOrCreate(
                        ['employees_department_id' => $employeeDepartment->id],
                        [
                            'name'       => $employeeDepartment->name,
                            'code'       => $this->resolveUniqueCode($employeeDepartment),
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

    protected function resolveUniqueCode(EmployeeDepartment $employeeDepartment): string
    {
        $existing = Department::query()
            ->where('employees_department_id', $employeeDepartment->id)
            ->value('code');

        if (is_string($existing) && $existing !== '') {
            $this->rememberCode($existing);

            return $existing;
        }

        $slugCode = strtoupper(Str::limit(Str::slug($employeeDepartment->name, ''), 4, ''));

        $candidates = array_filter([
            $slugCode !== '' ? $slugCode : null,
            'D'.str_pad((string) $employeeDepartment->id, 3, '0', STR_PAD_LEFT),
            str_pad((string) $employeeDepartment->id, 4, '0', STR_PAD_LEFT),
        ]);

        foreach ($candidates as $candidate) {
            if ($this->codeIsAvailable($candidate, $employeeDepartment->id)) {
                $this->rememberCode($candidate);

                return $candidate;
            }
        }

        $fallback = 'D'.str_pad((string) ($employeeDepartment->id % 1000), 3, '0', STR_PAD_LEFT);
        $suffix = 0;

        while (! $this->codeIsAvailable($fallback, $employeeDepartment->id)) {
            $suffix++;
            $fallback = Str::limit('D'.str_pad((string) (($employeeDepartment->id + $suffix) % 1000), 3, '0', STR_PAD_LEFT), 4, '');
        }

        $this->rememberCode($fallback);

        return $fallback;
    }

    protected function codeIsAvailable(string $code, int $employeeDepartmentId): bool
    {
        if ($code === '' || in_array($code, $this->usedCodes, true)) {
            return false;
        }

        return ! Department::query()
            ->where('code', $code)
            ->where(function ($query) use ($employeeDepartmentId): void {
                $query->whereNull('employees_department_id')
                    ->orWhere('employees_department_id', '!=', $employeeDepartmentId);
            })
            ->exists();
    }

    protected function rememberCode(string $code): void
    {
        if (! in_array($code, $this->usedCodes, true)) {
            $this->usedCodes[] = $code;
        }
    }
}
