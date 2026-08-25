<?php

namespace App\Services\Security;

use App\Enums\ModuleAccessLevel;
use Illuminate\Support\Collection;
use Webkul\Security\Filament\Resources\RoleResource;

class ModulePermissionResolver
{
    /** @var array<string, list<string>>|null */
    private static ?array $permissionsByModuleCache = null;

    /**
     * @return list<string>
     */
    public function getModuleNames(): array
    {
        return array_keys($this->getPermissionsByModule());
    }

    /**
     * @return array<string, list<string>>
     */
    public function getPermissionsByModule(): array
    {
        if (self::$permissionsByModuleCache !== null) {
            return self::$permissionsByModuleCache;
        }

        $grouped = [];

        foreach (RoleResource::getPluginResources() ?? [] as $module => $resources) {
            $permissions = collect($resources)
                ->flatMap(fn (array $entity): array => array_keys(RoleResource::getResourcePermissionOptions($entity)))
                ->unique()
                ->values()
                ->all();

            if ($permissions !== []) {
                $grouped[$module] = array_values(array_unique(array_merge($grouped[$module] ?? [], $permissions)));
            }
        }

        foreach (RoleResource::getPluginPages() ?? [] as $module => $pages) {
            $permissions = collect($pages)
                ->flatMap(fn (array $page): array => array_keys($page['permissions'] ?? []))
                ->unique()
                ->values()
                ->all();

            if ($permissions !== []) {
                $grouped[$module] = array_values(array_unique(array_merge($grouped[$module] ?? [], $permissions)));
            }
        }

        foreach (RoleResource::getPluginWidgets() ?? [] as $module => $widgets) {
            $permissions = collect($widgets)
                ->flatMap(fn (array $widget): array => array_keys($widget['permissions'] ?? []))
                ->unique()
                ->values()
                ->all();

            if ($permissions !== []) {
                $grouped[$module] = array_values(array_unique(array_merge($grouped[$module] ?? [], $permissions)));
            }
        }

        $customPermissions = $this->getCustomPermissions();

        if ($customPermissions !== []) {
            $grouped['Extensions'] = array_values(array_unique(array_merge(
                $grouped['Extensions'] ?? [],
                $customPermissions,
            )));
        }

        ksort($grouped);

        return self::$permissionsByModuleCache = $grouped;
    }

    /**
     * @return list<string>
     */
    public function getModulePermissions(string $module): array
    {
        return $this->getPermissionsByModule()[$module] ?? [];
    }

    public function getModulePermissionCount(string $module): int
    {
        return count($this->getModulePermissions($module));
    }

    /**
     * @param  array<string, string>  $moduleAccess
     * @return Collection<int, string>
     */
    public function resolveLevels(array $moduleAccess): Collection
    {
        $permissions = collect();

        foreach ($moduleAccess as $module => $levelValue) {
            $level = ModuleAccessLevel::tryFrom((string) $levelValue);

            if ($level === null || $level === ModuleAccessLevel::None) {
                continue;
            }

            $permissions = $permissions->merge(
                $this->resolveModuleLevel($module, $level)
            );
        }

        return $permissions->unique()->values();
    }

    /**
     * @return Collection<int, string>
     */
    public function resolveModuleLevel(string $module, ModuleAccessLevel $level): Collection
    {
        $modulePermissions = collect($this->getModulePermissions($module));

        if ($modulePermissions->isEmpty()) {
            return collect();
        }

        if ($level === ModuleAccessLevel::Full) {
            return $modulePermissions->values();
        }

        if ($level === ModuleAccessLevel::None) {
            return collect();
        }

        $allowedPrefixes = match ($level) {
            ModuleAccessLevel::View     => ['view', 'view_any', 'page_', 'widget_'],
            ModuleAccessLevel::Standard => ['view', 'view_any', 'create', 'update', 'page_', 'widget_'],
            default                     => [],
        };

        return $modulePermissions
            ->filter(function (string $permission) use ($allowedPrefixes): bool {
                if (str_starts_with($permission, 'page_') || str_starts_with($permission, 'widget_')) {
                    return in_array(str_starts_with($permission, 'page_') ? 'page_' : 'widget_', $allowedPrefixes, true);
                }

                foreach ($allowedPrefixes as $prefix) {
                    if (in_array($prefix, ['page_', 'widget_'], true)) {
                        continue;
                    }

                    if (preg_match('/^'.preg_quote($prefix, '/').'(_|$)/', $permission) === 1) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public function inferModuleAccess(Collection $assignedPermissions): array
    {
        $assigned = $assignedPermissions->values();
        $result = [];

        foreach ($this->getModuleNames() as $module) {
            $modulePermissions = collect($this->getModulePermissions($module));

            if ($modulePermissions->isEmpty()) {
                continue;
            }

            $selected = $modulePermissions->intersect($assigned);

            if ($selected->isEmpty()) {
                $result[$module] = ModuleAccessLevel::None->value;

                continue;
            }

            if ($selected->count() === $modulePermissions->count()) {
                $result[$module] = ModuleAccessLevel::Full->value;

                continue;
            }

            $result[$module] = $this->detectLevel($module, $selected)->value;
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function getModuleLabels(): array
    {
        return collect($this->getModuleNames())
            ->mapWithKeys(fn (string $module): array => [
                $module => __('role-management.modules.'.$module, [], null) !== 'role-management.modules.'.$module
                    ? __('role-management.modules.'.$module)
                    : $module,
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    public function getCustomPermissions(): array
    {
        $configured = config('filament-shield.custom_permissions', []);

        return is_array($configured) ? array_values($configured) : [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function getCustomPermissionsGrouped(): array
    {
        $grouped = [];

        foreach ($this->getCustomPermissions() as $permission) {
            $module = $this->mapCustomPermissionToModule($permission);
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    public function countSelectedInModule(string $module, Collection $permissions): int
    {
        return collect($this->getModulePermissions($module))
            ->intersect($permissions)
            ->count();
    }

    private function detectLevel(string $module, Collection $selected): ModuleAccessLevel
    {
        $viewOnly = $this->resolveModuleLevel($module, ModuleAccessLevel::View);
        $standard = $this->resolveModuleLevel($module, ModuleAccessLevel::Standard);

        if ($selected->sort()->values()->all() === $viewOnly->sort()->values()->all()) {
            return ModuleAccessLevel::View;
        }

        if ($selected->intersect($standard)->count() === $standard->count() && $selected->count() === $standard->count()) {
            return ModuleAccessLevel::Standard;
        }

        return ModuleAccessLevel::Standard;
    }

    private function mapCustomPermissionToModule(string $permission): string
    {
        return match (true) {
            str_contains($permission, 'employee')                                                  => 'Employee',
            str_contains($permission, 'assets') || str_contains($permission, 'borrowing')          => 'Assets',
            str_contains($permission, 'inventory')                                                 => 'Inventory',
            str_contains($permission, 'purchase') || str_contains($permission, 'InternalRequests') => 'Purchase',
            str_contains($permission, 'navigation')                                                => 'Support',
            default                                                                                => 'Extensions',
        };
    }
}
