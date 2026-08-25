<?php

use App\Enums\ModuleAccessLevel;
use App\Services\Security\ModulePermissionResolver;
use App\Services\Security\RolePermissionResolver;
use App\Services\Security\RoleTemplateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Config;
use Webkul\Security\Models\Role;

beforeEach(function (): void {
    Config::set('role-templates', [
        'employee' => [
            'label'       => 'role-management.templates.employee.label',
            'description' => 'role-management.templates.employee.description',
            'modules'     => [],
            'permissions' => [
                'page_MyEmployeeProfile',
                'page_MyEmployeeSubmissions',
            ],
        ],
    ]);
});

it('resolves employee template permissions', function (): void {
    $permissions = app(RoleTemplateService::class)->resolvePermissions('employee');

    expect($permissions->all())->toContain('page_MyEmployeeProfile', 'page_MyEmployeeSubmissions');
});

it('resolves module view level without delete permissions', function (): void {
    $resolver = app(ModulePermissionResolver::class);

    $inventoryModule = collect($resolver->getModuleNames())
        ->first(fn (string $module): bool => str_contains(strtolower($module), 'invent'));

    if ($inventoryModule === null) {
        $this->markTestSkipped('Inventory module permissions are not registered.');
    }

    $resolved = $resolver->resolveLevels([
        $inventoryModule => ModuleAccessLevel::Standard->value,
    ]);

    expect($resolved->contains(fn (string $permission): bool => str_contains($permission, 'delete')))->toBeFalse()
        ->and($resolved->isNotEmpty())->toBeTrue();
});

it('creates a role from the employee template', function (): void {
    $role = Role::query()->create([
        'name'       => 'test_employee_'.uniqid(),
        'guard_name' => 'web',
    ]);

    $permissions = app(RolePermissionResolver::class)->resolve([
        'starting_point'       => 'template',
        'role_template'        => 'employee',
        'module_access'        => [],
        'advanced_editor_open' => false,
    ]);

    $role->syncPermissionsByNames($permissions);

    expect($role->fresh()->hasPermissionTo('page_MyEmployeeProfile'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('page_MyEmployeeSubmissions'))->toBeTrue();
});

it('clones permissions from an existing role', function (): void {
    $source = Role::query()->create([
        'name'       => 'source_role_'.uniqid(),
        'guard_name' => 'web',
    ]);

    $source->syncPermissionsByNames(['page_MyEmployeeProfile', 'page_MyRequests']);

    $target = Role::query()->create([
        'name'       => 'cloned_role_'.uniqid(),
        'guard_name' => 'web',
    ]);

    $permissions = app(RolePermissionResolver::class)->resolve([
        'starting_point'       => 'clone',
        'clone_role_id'        => $source->id,
        'module_access'        => [],
        'advanced_editor_open' => false,
    ]);

    $target->syncPermissionsByNames($permissions);

    expect($target->fresh()->hasPermissionTo('page_MyEmployeeProfile'))->toBeTrue()
        ->and($target->fresh()->hasPermissionTo('page_MyRequests'))->toBeTrue();
});

it('uses advanced permissions when the advanced editor is open', function (): void {
    $permissions = app(RolePermissionResolver::class)->resolve([
        'starting_point'       => 'blank',
        'module_access'        => [
            'Employee' => ModuleAccessLevel::None->value,
        ],
        'advanced_editor_open' => true,
        'advanced_permissions' => ['page_MyEmployeeProfile', 'custom_permission_key'],
    ]);

    expect($permissions->all())->toBe(['page_MyEmployeeProfile', 'custom_permission_key']);
});

it('updates module access on edit via resolver merge', function (): void {
    $role = Role::query()->create([
        'name'       => 'editable_role_'.uniqid(),
        'guard_name' => 'web',
    ]);

    $role->syncPermissionsByNames(['page_MyEmployeeProfile']);

    $resolver = app(ModulePermissionResolver::class);
    $employeeModule = collect($resolver->getModuleNames())
        ->first(fn (string $module): bool => $module === 'Employee' || str_contains(strtolower($module), 'employee'));

    if ($employeeModule === null) {
        $this->markTestSkipped('Employee module permissions are not registered.');
    }

    $moduleAccess = $resolver->inferModuleAccess(collect(['page_MyEmployeeProfile']));
    $moduleAccess[$employeeModule] = ModuleAccessLevel::Full->value;

    $permissions = app(RolePermissionResolver::class)->resolve([
        'starting_point'       => 'blank',
        'module_access'        => $moduleAccess,
        'advanced_editor_open' => false,
    ], $role);

    $role->syncPermissionsByNames($permissions);

    expect($role->permissions()->count())->toBeGreaterThan(1);
});

it('protects system roles from deletion', function (): void {
    $adminRole = Role::query()->firstOrCreate([
        'name'       => 'admin',
        'guard_name' => 'web',
    ]);

    expect($adminRole->isSystemRole())->toBeTrue();

    expect(fn () => $adminRole->delete())->toThrow(AuthorizationException::class);
});

it('exposes module labels for the hybrid form', function (): void {
    $labels = app(ModulePermissionResolver::class)->getModuleLabels();

    expect($labels)->toBeArray()->not->toBeEmpty();
});

it('builds a permission summary for review step', function (): void {
    $summary = app(RolePermissionResolver::class)->buildSummary([
        'name'           => 'Reviewer Role',
        'guard_name'     => 'web',
        'starting_point' => 'template',
        'role_template'  => 'employee',
        'module_access'  => [],
    ]);

    expect($summary)->toHaveKeys([
        'total_permissions',
        'selected_permissions',
        'modules_with_access',
        'total_modules',
        'module_summary',
    ])->and($summary['selected_permissions'])->toBeGreaterThan(0);
});
