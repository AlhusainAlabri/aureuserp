<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeResumeLineType;
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

function createEmployeeForFlowTest(?User $user = null, array $attributes = []): Employee
{
    $user ??= createEmployeeAdminUser();

    $company = Company::create([
        'name'       => 'Flow Test Company',
        'company_id' => 'flow-'.uniqid(),
    ]);

    return Employee::create([
        'user_id'    => $user->id,
        'creator_id' => $user->id,
        'company_id' => $company->id,
        'name'       => $attributes['name'] ?? 'Flow Test Employee',
        'work_email' => $attributes['work_email'] ?? 'flow.employee@example.com',
        'is_active'  => $attributes['is_active'] ?? true,
        ...$attributes,
    ]);
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
