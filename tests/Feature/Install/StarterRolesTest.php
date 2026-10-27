<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('creates the roles the approval flows rely on', function (): void {
    $this->artisan('erp:seed-starter-roles')->assertSuccessful();

    foreach (['employee', 'manager', 'hr_manager', 'finance_manager', 'general_manager', 'admin_manager', 'sales_manager'] as $name) {
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

it('gives the sales role what a salesperson needs and nothing destructive', function (): void {
    Permission::query()->firstOrCreate(['name' => 'view_any_sale_quotation', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'create_sale_order', 'guard_name' => 'web']);

    $this->artisan('erp:seed-starter-roles')->assertSuccessful();

    $role = Role::query()->where('name', 'sales_manager')->firstOrFail();

    expect($role->hasPermissionTo('view_any_sale_quotation'))->toBeTrue()
        ->and($role->hasPermissionTo('create_sale_order'))->toBeTrue()
        ->and($role->permissions->pluck('name')->contains('force_delete_any_sale_order'))->toBeFalse()
        ->and($role->permissions->pluck('name')->contains('create_account_move'))->toBeFalse();
});
