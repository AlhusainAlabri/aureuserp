<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('creates the roles the approval flows rely on', function (): void {
    $this->artisan('erp:seed-starter-roles')->assertSuccessful();

    foreach (['employee', 'manager', 'hr_manager', 'finance_manager', 'general_manager', 'admin_manager'] as $name) {
        expect(Role::query()->where('name', $name)->where('guard_name', 'web')->exists())->toBeTrue($name);
    }
});

it('is idempotent and keeps permissions a customer added to a starter role', function (): void {
    $this->artisan('erp:seed-starter-roles')->assertSuccessful();

    $extra = Permission::query()->firstOrCreate(['name' => 'qa_custom_permission', 'guard_name' => 'web']);
    $role = Role::query()->where('name', 'manager')->firstOrFail();
    $role->givePermissionTo($extra);

    $before = $role->permissions()->count();

    $this->artisan('erp:seed-starter-roles')->assertSuccessful();

    expect($role->fresh()->permissions()->count())->toBe($before)
        ->and($role->fresh()->permissions->pluck('name')->contains('qa_custom_permission'))->toBeTrue()
        ->and(Role::query()->where('name', 'manager')->count())->toBe(1);
});

it('ships approval flows switched off until a customer assigns approvers', function (): void {
    foreach (glob(base_path('database/seeders/*ApprovalFlowSeeder.php')) as $seeder) {
        expect(file_get_contents($seeder))->not->toMatch("/'is_active'\\s*=>\\s*true/");
    }
});
