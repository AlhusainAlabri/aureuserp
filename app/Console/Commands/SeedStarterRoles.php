<?php

namespace App\Console\Commands;

use App\Services\Security\RoleTemplateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Signature('erp:seed-starter-roles')]
#[Description('Create the starter roles (employee, manager, HR, finance, general manager) from the role templates')]
class SeedStarterRoles extends Command
{
    /**
     * Roles the approval flows refer to that have no template of their own.
     *
     * @var array<string, string>
     */
    protected array $templateForRole = [
        'admin_manager' => 'manager',
    ];

    public function handle(RoleTemplateService $templates): int
    {
        if (! Schema::hasTable('roles')) {
            return self::SUCCESS;
        }

        $created = 0;

        foreach (array_keys(config('role-templates', [])) as $roleName) {
            $this->syncRole($templates, $roleName, $roleName) && $created++;
        }

        foreach ($this->templateForRole as $roleName => $templateKey) {
            $this->syncRole($templates, $roleName, $templateKey) && $created++;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Starter roles ready ({$created} created).");

        return self::SUCCESS;
    }

    /**
     * Create the role if it is missing and add any template permission that now exists.
     * Permissions are only ever added, so changes a customer made to a role are kept.
     *
     * @return bool whether the role was newly created
     */
    protected function syncRole(RoleTemplateService $templates, string $roleName, string $templateKey): bool
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        // A template can name permissions from plugins that are not installed yet.
        $existing = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $templates->resolvePermissions($templateKey)->all())
            ->pluck('name')
            ->all();

        $role->givePermissionTo($existing);

        return $role->wasRecentlyCreated;
    }
}
