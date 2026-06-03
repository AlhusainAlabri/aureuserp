<?php

use App\Support\Dashboard\OrgAlertCatalog;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Security\Models\User;

function orgAlertUserWithRole(string $roleName): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

it('returns alert rows sorted by severity then count', function (): void {
    orgAlertUserWithRole('general_manager');

    $alerts = OrgAlertCatalog::alerts();

    expect($alerts)->toBeInstanceOf(Collection::class);

    if ($alerts->isEmpty()) {
        expect($alerts)->toBeEmpty();

        return;
    }

    $severities = $alerts->pluck('severity')->all();
    $severityOrder = ['danger', 'warning', 'info'];

    $lastIndex = -1;

    foreach ($severities as $severity) {
        $index = array_search($severity, $severityOrder, true);
        expect($index)->toBeGreaterThanOrEqual($lastIndex);
        $lastIndex = $index;
    }

    $alerts->each(function (array $alert): void {
        expect($alert)->toHaveKeys(['id', 'module', 'label', 'severity', 'count', 'url'])
            ->and($alert['count'])->toBeGreaterThan(0);
    });
});

it('scopes employee alerts to personal operational items', function (): void {
    orgAlertUserWithRole('employee');

    $scopes = OrgAlertCatalog::scopesForUser();

    expect($scopes)->toBe([OrgAlertCatalog::SCOPE_EMPLOYEE]);
});

it('scopes general manager alerts to executive visibility', function (): void {
    orgAlertUserWithRole('general_manager');

    $scopes = OrgAlertCatalog::scopesForUser();

    expect($scopes)->toContain(OrgAlertCatalog::SCOPE_EXECUTIVE);
});
