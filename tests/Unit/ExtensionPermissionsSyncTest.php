<?php

use App\Support\ExtensionPermissionsSync;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

it('syncs hr extension permissions idempotently', function (): void {
    Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

    app(ExtensionPermissionsSync::class)->syncHr();

    $role = Role::query()->where('name', 'Admin')->first();

    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('close_employee_file'))->toBeTrue()
        ->and($role->hasPermissionTo('reopen_employee_file'))->toBeTrue();
});

it('registers the permissions sync extensions command', function (): void {
    expect(array_key_exists('permissions:sync-extensions', Artisan::all()))->toBeTrue();
});
