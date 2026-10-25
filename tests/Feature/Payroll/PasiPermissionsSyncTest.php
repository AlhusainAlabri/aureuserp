<?php

use App\Support\ExtensionPermissionsSync;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;

const PASI_SYNC_PERMISSIONS = [
    'view_any_payroll_pasi::rule',
    'view_payroll_pasi::rule',
    'create_payroll_pasi::rule',
    'update_payroll_pasi::rule',
    'page_payroll_pasi_contribution_schedule',
];

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function pasiSyncRole(string $name, array $permissions): Role
{
    $role = Role::findOrCreate($name, 'web');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->syncPermissions($permissions);

    return $role;
}

it('PasiPermissionsSyncTest: grants the PASI permissions to roles that can update salary components', function (): void {
    $manager = pasiSyncRole('pasi-sync-manager', ['view_any_payroll_salary::component', 'update_payroll_salary::component']);
    $viewer = pasiSyncRole('pasi-sync-viewer', ['view_any_payroll_salary::component']);

    app(ExtensionPermissionsSync::class)->syncPayroll();
    app(ExtensionPermissionsSync::class)->syncPayroll();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (PASI_SYNC_PERMISSIONS as $permission) {
        expect($manager->fresh()->hasPermissionTo($permission))->toBeTrue()
            ->and($viewer->fresh()->hasPermissionTo($permission))->toBeFalse();
    }

    expect($manager->fresh()->permissions()->whereIn('name', PASI_SYNC_PERMISSIONS)->count())->toBe(count(PASI_SYNC_PERMISSIONS));
});

it('PasiPermissionsSyncTest: syncAll includes the payroll step', function (): void {
    $manager = pasiSyncRole('pasi-sync-all', ['update_payroll_salary::component']);

    app(ExtensionPermissionsSync::class)->syncAll();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($manager->fresh()->hasPermissionTo('view_any_payroll_pasi::rule'))->toBeTrue();
});
