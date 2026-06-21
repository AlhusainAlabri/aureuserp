<?php

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeResumeLineType;
use Webkul\Employee\Models\EmploymentType;
use Webkul\Employee\Models\Skill;
use Webkul\Employee\Models\SkillLevel;
use Webkul\Employee\Models\SkillType;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

function createEmployeeAdminUser(array $permissions = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create([
        'is_active'           => true,
        'resource_permission' => PermissionType::GLOBAL,
    ]));

    $defaultPermissions = [
        'view_any_employee_employee',
        'view_employee_employee',
        'create_employee_employee',
        'update_employee_employee',
        'delete_employee_employee',
        'view_any_employee_employee::skill',
        'view_employee_employee::skill',
        'create_employee_employee::skill',
        'update_employee_employee::skill',
    ];

    foreach (array_unique([...$defaultPermissions, ...$permissions]) as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function createEmployeeDepartmentForTest(User $user): Department
{
    return Department::query()->create([
        'name'       => 'Test Department '.uniqid(),
        'creator_id' => $user->id,
    ]);
}

function createEmployeeEmploymentTypeForTest(User $user): EmploymentType
{
    return EmploymentType::query()->create([
        'name'       => 'Permanent',
        'code'       => 'permanent-'.uniqid(),
        'creator_id' => $user->id,
    ]);
}

function createEmployeeCompanyForTest(): Company
{
    return Company::create([
        'name'       => 'Test Company '.uniqid(),
        'company_id' => 'test-'.uniqid(),
    ]);
}

/**
 * @return array<string, mixed>
 */
function requiredEmployeeFormData(User $user, array $overrides = []): array
{
    $company = $overrides['company_id'] ?? createEmployeeCompanyForTest()->id;
    $department = createEmployeeDepartmentForTest($user);
    $employmentType = createEmployeeEmploymentTypeForTest($user);

    $data = [
        'membership_type'    => 'employee',
        'mobile_phone'       => '+96890000000',
        'civil_id'           => '12345678901',
        'employment_type_id' => $employmentType->id,
        'company_id'         => $company,
    ];

    if (Schema::hasTable('department_employee')) {
        $data['departments'] = [$department->id];
        $data['department_id'] = $department->id;
    } else {
        $data['department_id'] = $department->id;
    }

    return [...$data, ...$overrides];
}

function createEmployeeForFlowTest(?User $user = null, array $attributes = []): Employee
{
    $user ??= createEmployeeAdminUser();

    $company = isset($attributes['company_id'])
        ? Company::query()->findOrFail($attributes['company_id'])
        : createEmployeeCompanyForTest();

    $department = isset($attributes['department_id'])
        ? Department::query()->findOrFail($attributes['department_id'])
        : createEmployeeDepartmentForTest($user);

    $employmentType = isset($attributes['employment_type_id'])
        ? EmploymentType::query()->findOrFail($attributes['employment_type_id'])
        : createEmployeeEmploymentTypeForTest($user);

    $employee = Employee::create([
        'user_id'            => $user->id,
        'creator_id'         => $user->id,
        'company_id'         => $company->id,
        'department_id'      => $department->id,
        'employment_type_id' => $employmentType->id,
        'mobile_phone'       => $attributes['mobile_phone'] ?? '+96890000000',
        'civil_id'           => $attributes['civil_id'] ?? '12345678901',
        'name'               => $attributes['name'] ?? 'Flow Test Employee',
        'work_email'         => $attributes['work_email'] ?? 'flow.employee@example.com',
        'is_active'          => $attributes['is_active'] ?? true,
        ...$attributes,
    ]);

    if (Schema::hasTable('department_employee')) {
        $employee->departments()->sync([
            $department->id => ['is_primary' => true, 'start_date' => now()->toDateString()],
        ]);
    }

    return $employee;
}

function createSkillCatalog(User $user): array
{
    $skillType = SkillType::create([
        'name'       => 'Technical',
        'color'      => '#3b82f6',
        'creator_id' => $user->id,
        'is_active'  => true,
    ]);

    $skill = Skill::create([
        'name'          => 'PHP',
        'skill_type_id' => $skillType->id,
        'creator_id'    => $user->id,
    ]);

    $skillLevel = SkillLevel::create([
        'name'          => 'Expert',
        'level'         => 100,
        'skill_type_id' => $skillType->id,
        'creator_id'    => $user->id,
    ]);

    return compact('skillType', 'skill', 'skillLevel');
}

function createResumeLineType(User $user): EmployeeResumeLineType
{
    return EmployeeResumeLineType::create([
        'name'       => 'Experience',
        'sort'       => 1,
        'creator_id' => $user->id,
    ]);
}
