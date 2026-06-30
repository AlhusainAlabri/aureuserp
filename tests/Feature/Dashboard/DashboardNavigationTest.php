<?php

use App\Support\Dashboard\DashboardNavigation;
use App\Support\Dashboard\OrgAlertCatalog;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Webkul\PluginManager\Package;
use Webkul\Security\Models\User;

it('returns null correspondence urls when correspondence plugin is not installed', function (): void {
    if (Package::isPluginInstalled('correspondence')) {
        test()->markTestSkipped('Correspondence plugin is installed.');
    }

    expect(DashboardNavigation::correspondenceIndexUrl())->toBeNull()
        ->and(DashboardNavigation::correspondenceApprovalsUrl())->toBeNull();
});

it('does not throw when building org alerts without correspondence routes', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $role = Role::firstOrCreate(['name' => 'general_manager', 'guard_name' => 'web']);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    expect(fn () => OrgAlertCatalog::alerts())->not->toThrow(RouteNotFoundException::class);
});
