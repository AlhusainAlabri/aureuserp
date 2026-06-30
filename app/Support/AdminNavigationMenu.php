<?php

namespace App\Support;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Collection;

class AdminNavigationMenu
{
    /**
     * Preferred primary URLs per registered menu icon (first match wins).
     * Used when Filament cannot merge plugin items into translated navigation groups.
     *
     * @var array<string, array<int, string>>
     */
    /**
     * Direct paths when no matching navigation item is registered (e.g. cluster-only resources).
     *
     * @var array<string, string>
     */
    private const PRIMARY_FALLBACK_PATHS = [
        'icon-sales' => '/admin/sale/orders/quotations',
    ];

    private const PRIMARY_URL_PATTERNS = [
        'icon-dashboard'        => ['/admin$', '/admin/org-overview', '/admin/overview', '/admin/home'],
        'icon-contacts'         => ['/admin/contact/contacts'],
        'icon-sales'            => ['/admin/sale/orders/quotations', '/admin/sale/orders'],
        'icon-purchases'        => ['/admin/purchase/orders'],
        'icon-manufacturing'    => ['/admin/manufacturing/operations'],
        'icon-inventories'      => ['/admin/inventory/dashboard'],
        'icon-invoices'         => ['/admin/invoices/customers', '/admin/invoices/vendors'],
        'icon-accounting'       => ['/admin/accounting/customers', '/admin/accounting/overview'],
        'icon-projects'         => ['/admin/projects/task-hub', '/admin/project/projects'],
        'icon-meetings'         => ['/admin/meetings/dashboard'],
        'icon-my-notes'         => ['/admin/my-notes'],
        'icon-correspondence'   => ['/admin/correspondence/dashboard'],
        'icon-document-archive' => ['/admin/document-archive/dashboard'],
        'icon-assets'           => ['/admin/assets/dashboard'],
        'icon-employees'        => ['/admin/employees/employees'],
        'icon-payroll'          => ['/admin/payroll/employee-components'],
        'icon-time-offs'        => ['/admin/time-off/dashboard'],
        'icon-recruitments'     => ['/admin/recruitments/applications'],
        'icon-website'          => ['/admin/website'],
        'icon-approvals'        => ['/admin/approval-flows'],
        'icon-plugin'           => ['/admin/plugins'],
        'icon-settings'         => ['/admin/companies'],
    ];

    /**
     * @param  array<NavigationGroup>  $navigationGroups
     * @param  array<NavigationGroup|string>  $registeredGroups
     * @return Collection<int, array{label: string, icon: string, url: string, isActive: bool}>
     */
    public static function primaryMenuItems(array $navigationGroups, array $registeredGroups): Collection
    {
        $flatItems = collect($navigationGroups)
            ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())->all());

        return collect($registeredGroups)
            ->map(fn (NavigationGroup|string $group): NavigationGroup => $group instanceof NavigationGroup
                ? $group
                : NavigationGroup::make($group))
            ->filter(fn (NavigationGroup $group): bool => filled($group->getIcon()) && filled($group->getLabel()))
            ->map(function (NavigationGroup $registered) use ($navigationGroups, $flatItems): ?array {
                $icon = (string) $registered->getIcon();
                $label = $registered->getLabel();

                $matchedGroup = collect($navigationGroups)->first(
                    fn (NavigationGroup $group): bool => $group->getIcon() === $icon,
                );

                $url = static::resolveUrlByIcon($icon, $flatItems)
                    ?? static::resolveFallbackUrl($icon)
                    ?? ($matchedGroup ? static::resolveGroupUrl($matchedGroup) : null);

                if (blank($url)) {
                    return null;
                }

                return [
                    'label'    => $label,
                    'icon'     => $icon,
                    'url'      => $url,
                    'isActive' => $matchedGroup?->isActive() ?? false,
                ];
            })
            ->filter()
            ->values();
    }

    private static function resolveGroupUrl(NavigationGroup $group): ?string
    {
        $items = collect($group->getItems())
            ->filter(fn (NavigationItem $item): bool => $item->isVisible())
            ->sortBy(fn (NavigationItem $item): int => $item->getSort());

        return $items
            ->first(fn (NavigationItem $item): bool => $item->getSort() >= 0)
            ?->getUrl()
            ?? $items->first()?->getUrl();
    }

    /**
     * @param  Collection<int, NavigationItem>  $items
     */
    private static function resolveUrlByIcon(string $icon, Collection $items): ?string
    {
        $patterns = self::PRIMARY_URL_PATTERNS[$icon] ?? [];

        foreach ($patterns as $pattern) {
            $match = $items
                ->sortBy(fn (NavigationItem $item): int => $item->getSort())
                ->first(function (NavigationItem $item) use ($pattern): bool {
                    if (! $item->isVisible()) {
                        return false;
                    }

                    $url = $item->getUrl();

                    if (blank($url)) {
                        return false;
                    }

                    $path = parse_url($url, PHP_URL_PATH) ?? $url;

                    if (str_ends_with($pattern, '$')) {
                        return (bool) preg_match('#'.preg_quote(rtrim($pattern, '$'), '#').'$#', $path);
                    }

                    return str_contains($path, $pattern);
                });

            if ($match !== null) {
                return $match->getUrl();
            }
        }

        return null;
    }

    private static function resolveFallbackUrl(string $icon): ?string
    {
        $path = self::PRIMARY_FALLBACK_PATHS[$icon] ?? null;

        if (blank($path)) {
            return null;
        }

        return url($path);
    }
}
