<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EmployeeRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(Role::class)) {
            return;
        }

        $role = Role::query()->firstOrCreate([
            'name'       => 'employee',
            'guard_name' => 'web',
        ]);

        $permissions = [
            'page_MyEmployeeProfile',
            'page_MyEmployeeSubmissions',
            'page_MySelfAssessment',
            'page_MyWarnings',
            'page_MyRequests',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        $existingPermissions = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->all();

        $role->syncPermissions($existingPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
