<?php

namespace Database\Seeders;

use App\Services\Hr\EmployeeDepartmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class HrDemoEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('employees_employees')) {
            return;
        }

        $this->call(OmanOrgStructureSeeder::class);

        $user = User::query()->firstOrCreate(
            ['email' => 'toney.morar@example.org'],
            [
                'name'              => 'Tony Morar',
                'password'          => Hash::make('Oman@999'),
                'is_active'         => true,
                'language'          => 'ar',
            ],
        );

        if ($user->language !== 'ar') {
            $user->forceFill(['language' => 'ar'])->saveQuietly();
        }

        $companyId = Company::query()->value('id');
        $department = Department::query()
            ->where('name', 'Social Research Department')
            ->first();

        $employee = Employee::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'name'         => 'Tony Morar',
                'work_email'   => 'toney.morar@example.org',
                'company_id'   => $companyId,
                'department_id'=> $department?->id,
                'creator_id'   => User::query()->value('id'),
            ],
        );

        $payload = array_filter([
            'name'            => 'Tony Morar',
            'work_email'      => 'toney.morar@example.org',
            'mobile_phone'    => '96891234567',
            'company_id'      => $companyId,
            'department_id'   => $department?->id,
            'civil_id'        => Schema::hasColumn('employees_employees', 'civil_id') ? '12345678' : null,
            'civil_id_expiry' => Schema::hasColumn('employees_employees', 'civil_id_expiry')
                ? now()->addMonths(8)->toDateString()
                : null,
            'primary_job_responsibilities' => Schema::hasColumn('employees_employees', 'primary_job_responsibilities')
                ? 'إعداد التقارير البحثية، متابعة حالات الأسر المستفيدة، وتحديث سجلات المتابعة.'
                : null,
        ], fn (mixed $value): bool => $value !== null);

        $employee->forceFill($payload)->saveQuietly();

        if ($department && Schema::hasTable('department_employee') && class_exists(EmployeeDepartmentService::class)) {
            app(EmployeeDepartmentService::class)->syncDepartments(
                $employee,
                [$department->id],
                $department->id,
            );
        }
    }
}
