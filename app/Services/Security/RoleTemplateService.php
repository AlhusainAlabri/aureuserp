<?php

namespace App\Services\Security;

use Illuminate\Support\Collection;

class RoleTemplateService
{
    public function __construct(
        private readonly ModulePermissionResolver $modulePermissionResolver,
    ) {}

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function getTemplateOptions(): array
    {
        $options = [];

        foreach (config('role-templates', []) as $key => $template) {
            $options[$key] = [
                'label'       => __($template['label']),
                'description' => __($template['description']),
            ];
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function getTemplateSelectOptions(): array
    {
        return collect($this->getTemplateOptions())
            ->mapWithKeys(fn (array $template, string $key): array => [$key => $template['label']])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function getModuleAccessForTemplate(?string $templateKey): array
    {
        if ($templateKey === null || $templateKey === '') {
            return [];
        }

        $template = config("role-templates.{$templateKey}");

        if (! is_array($template)) {
            return [];
        }

        return $template['modules'] ?? [];
    }

    /**
     * @return Collection<int, string>
     */
    public function resolvePermissions(?string $templateKey): Collection
    {
        if ($templateKey === null || $templateKey === '') {
            return collect();
        }

        $template = config("role-templates.{$templateKey}");

        if (! is_array($template)) {
            return collect();
        }

        $fromModules = $this->modulePermissionResolver->resolveLevels($template['modules'] ?? []);
        $explicit = collect($template['permissions'] ?? []);

        return $fromModules
            ->merge($explicit)
            ->unique()
            ->values();
    }

    public function hasTemplate(?string $templateKey): bool
    {
        return is_string($templateKey)
            && $templateKey !== ''
            && is_array(config("role-templates.{$templateKey}"));
    }
}
