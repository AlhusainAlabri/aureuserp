<?php

namespace App\Services\Security;

use Illuminate\Support\Collection;
use Webkul\Security\Filament\Resources\RoleResource;
use Webkul\Security\Models\Role;

class RolePermissionResolver
{
    public function __construct(
        private readonly RoleTemplateService $roleTemplateService,
        private readonly ModulePermissionResolver $modulePermissionResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, string>
     */
    public function resolve(array $data, ?Role $existingRole = null): Collection
    {
        if ($this->shouldUseAdvancedPermissions($data)) {
            return collect($data['advanced_permissions'] ?? [])
                ->filter(fn ($permission): bool => is_string($permission) && $permission !== '')
                ->unique()
                ->values();
        }

        $permissions = $this->resolveBasePermissions($data, $existingRole);

        if (! empty($data['module_access']) && is_array($data['module_access'])) {
            $permissions = $this->mergeModuleAccess(
                $permissions,
                $data['module_access'],
            );
        }

        return $permissions->unique()->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveBasePermissions(array $data, ?Role $existingRole): Collection
    {
        $startingPoint = $data['starting_point'] ?? 'blank';

        return match ($startingPoint) {
            'template' => $this->roleTemplateService->resolvePermissions($data['role_template'] ?? null),
            'clone'    => $this->resolveClonePermissions($data['clone_role_id'] ?? null),
            default    => $existingRole
                ? $existingRole->permissions()->pluck('name')
                : collect(),
        };
    }

    private function resolveClonePermissions(mixed $roleId): Collection
    {
        if (blank($roleId)) {
            return collect();
        }

        $role = Role::query()->find($roleId);

        if ($role === null) {
            return collect();
        }

        return $role->permissions()->pluck('name');
    }

    /**
     * @param  array<string, string>  $moduleAccess
     */
    private function mergeModuleAccess(Collection $existing, array $moduleAccess): Collection
    {
        $permissions = $existing;

        foreach ($moduleAccess as $module => $level) {
            $modulePermissions = collect($this->modulePermissionResolver->getModulePermissions($module));

            $permissions = $permissions->reject(
                fn (string $permission): bool => $modulePermissions->contains($permission)
            );

            $levelPermissions = $this->modulePermissionResolver->resolveLevels([
                $module => $level,
            ]);

            $permissions = $permissions->merge($levelPermissions);
        }

        return $permissions;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function shouldUseAdvancedPermissions(array $data): bool
    {
        if (! ($data['advanced_editor_open'] ?? false)) {
            return false;
        }

        $advanced = collect($data['advanced_permissions'] ?? [])
            ->filter(fn ($permission): bool => is_string($permission) && $permission !== '');

        return $advanced->isNotEmpty();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     total_permissions: int,
     *     selected_permissions: int,
     *     modules_with_access: int,
     *     total_modules: int,
     *     module_summary: array<int, array{module: string, level: string, selected: int, total: int}>
     * }
     */
    public function buildSummary(array $data): array
    {
        $permissions = $this->resolve($data);
        $moduleAccess = is_array($data['module_access'] ?? null) ? $data['module_access'] : [];
        $allModules = $this->modulePermissionResolver->getModuleNames();
        $totalAvailable = RoleResource::getAllFormPermissions()->count()
            + count($this->modulePermissionResolver->getCustomPermissions());

        $modulesWithAccess = collect($moduleAccess)
            ->filter(fn (string $level): bool => $level !== 'none')
            ->count();

        $moduleSummary = collect($allModules)
            ->map(function (string $module) use ($moduleAccess, $permissions): array {
                $total = $this->modulePermissionResolver->getModulePermissionCount($module);
                $selected = $this->modulePermissionResolver->countSelectedInModule($module, $permissions);

                return [
                    'module'   => $module,
                    'label'    => $this->modulePermissionResolver->getModuleLabels()[$module] ?? $module,
                    'level'    => $moduleAccess[$module] ?? 'none',
                    'selected' => $selected,
                    'total'    => $total,
                ];
            })
            ->filter(fn (array $row): bool => $row['total'] > 0)
            ->values()
            ->all();

        return [
            'total_permissions'    => $totalAvailable,
            'selected_permissions' => $permissions->count(),
            'modules_with_access'  => $modulesWithAccess,
            'total_modules'        => count($allModules),
            'module_summary'       => $moduleSummary,
        ];
    }
}
