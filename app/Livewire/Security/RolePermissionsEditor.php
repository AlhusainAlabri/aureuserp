<?php

namespace App\Livewire\Security;

use App\Services\Security\ModulePermissionResolver;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Webkul\Security\Filament\Resources\RoleResource;

class RolePermissionsEditor extends Component
{
    #[Modelable]
    public array $advancedPermissions = [];

    public bool $lazy = false;

    public string $search = '';

    public string $activeTab = 'resources';

    public function mount(bool $lazy = false): void
    {
        $this->lazy = $lazy;
    }

    public function selectModulePermissions(string $module): void
    {
        $resolver = app(ModulePermissionResolver::class);
        $modulePermissions = $resolver->getModulePermissions($module);

        $this->advancedPermissions = collect($this->advancedPermissions)
            ->merge($modulePermissions)
            ->unique()
            ->values()
            ->all();

        $this->dispatchAdvancedPermissionsUpdated();
    }

    public function deselectModulePermissions(string $module): void
    {
        $resolver = app(ModulePermissionResolver::class);
        $modulePermissions = collect($resolver->getModulePermissions($module));

        $this->advancedPermissions = collect($this->advancedPermissions)
            ->reject(fn (string $permission): bool => $modulePermissions->contains($permission))
            ->values()
            ->all();

        $this->dispatchAdvancedPermissionsUpdated();
    }

    public function updatedAdvancedPermissions(): void
    {
        $this->dispatchAdvancedPermissionsUpdated();
    }

    protected function dispatchAdvancedPermissionsUpdated(): void
    {
        $this->dispatch(
            'advanced-permissions-updated',
            permissions: $this->advancedPermissions,
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getFilteredPermissionGroups(): array
    {
        $search = strtolower(trim($this->search));

        return match ($this->activeTab) {
            'pages'   => $this->filterGroups($this->getPageGroups(), $search),
            'widgets' => $this->filterGroups($this->getWidgetGroups(), $search),
            'custom'  => $this->filterGroups($this->getCustomGroups(), $search),
            default   => $this->filterGroups($this->getResourceGroups(), $search),
        };
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getResourceGroups(): array
    {
        $groups = [];

        foreach (RoleResource::getPluginResources() ?? [] as $module => $resources) {
            $options = collect($resources)
                ->flatMap(fn (array $entity): array => RoleResource::getResourcePermissionOptions($entity))
                ->all();

            if ($options !== []) {
                $groups[$module] = $options;
            }
        }

        return $groups;
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getPageGroups(): array
    {
        $groups = [];

        foreach (RoleResource::getPluginPages() ?? [] as $module => $pages) {
            $options = collect($pages)
                ->flatMap(fn (array $page): array => $page['permissions'] ?? [])
                ->all();

            if ($options !== []) {
                $groups[$module] = $options;
            }
        }

        return $groups;
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getWidgetGroups(): array
    {
        $groups = [];

        foreach (RoleResource::getPluginWidgets() ?? [] as $module => $widgets) {
            $options = collect($widgets)
                ->flatMap(fn (array $widget): array => $widget['permissions'] ?? [])
                ->all();

            if ($options !== []) {
                $groups[$module] = $options;
            }
        }

        return $groups;
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getCustomGroups(): array
    {
        $resolver = app(ModulePermissionResolver::class);
        $grouped = $resolver->getCustomPermissionsGrouped();

        return collect($grouped)
            ->mapWithKeys(fn (array $permissions, string $module): array => [
                $module => collect($permissions)->mapWithKeys(fn (string $permission): array => [
                    $permission => $permission,
                ])->all(),
            ])
            ->all();
    }

    /**
     * @param  array<string, array<string, string>>  $groups
     * @return array<string, array<string, string>>
     */
    protected function filterGroups(array $groups, string $search): array
    {
        if ($search === '') {
            return $groups;
        }

        return collect($groups)
            ->map(function (array $options) use ($search): array {
                return collect($options)
                    ->filter(function (string $label, string $key) use ($search): bool {
                        return str_contains(strtolower($key), $search)
                            || str_contains(strtolower($label), $search);
                    })
                    ->all();
            })
            ->filter(fn (array $options): bool => $options !== [])
            ->all();
    }

    public function moduleProgress(string $module, array $options): string
    {
        $selected = collect($options)
            ->keys()
            ->intersect($this->advancedPermissions)
            ->count();

        return __('role-management.advanced.progress', [
            'selected' => $selected,
            'total'    => count($options),
        ]);
    }

    public function render(): View
    {
        return view('livewire.security.role-permissions-editor');
    }
}
