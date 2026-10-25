<?php

namespace App\Support\Navigation;

use App\Support\DashboardNavigationOrder;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Pages\Page as ResourcePage;
use Illuminate\Support\Collection;

class ModuleSidebarMenuBuilder
{
    /**
     * Build full ERP module navigation groups with cluster pages flattened inline.
     *
     * Filament only allows icons on the group **or** on its items — not both.
     * Parent items with children also require an icon, which conflicts with module group icons.
     * Cluster pages are therefore flattened as text-only links under each module group.
     *
     * @return array<NavigationGroup>
     */
    public static function build(): array
    {
        $panel = Filament::getCurrentPanel();

        if ($panel === null) {
            return [];
        }

        $navigation = Filament::getNavigation();

        /** @var Collection<string, class-string<Cluster>> $clustersByPath */
        $clustersByPath = static::clusterIndex();

        $groups = collect($panel->getNavigationGroups())
            ->map(fn (NavigationGroup|string $group): NavigationGroup => $group instanceof NavigationGroup
                ? $group
                : NavigationGroup::make($group))
            ->filter(fn (NavigationGroup $group): bool => filled($group->getIcon()) && filled($group->getLabel()))
            ->map(function (NavigationGroup $registered) use ($navigation, $clustersByPath): ?NavigationGroup {
                $matchedGroup = static::matchNavigationGroup($registered, $navigation);
                $aliasedItems = static::aliasedGroupItems($registered, $navigation);

                if ($matchedGroup === null && $aliasedItems === []) {
                    return null;
                }

                $items = collect($matchedGroup?->getItems() ?? [])
                    ->concat($aliasedItems)
                    ->filter(fn (NavigationItem $item): bool => $item->isVisible())
                    ->when(
                        $registered->getIcon() === 'icon-dashboard',
                        function (Collection $collection): Collection {
                            try {
                                return DashboardNavigationOrder::sort($collection->all());
                            } catch (\Throwable) {
                                return $collection;
                            }
                        },
                    )
                    ->flatMap(fn (NavigationItem $item): array => static::flattenNavigationItem($item, $clustersByPath))
                    ->unique(fn (NavigationItem $item): string => (string) $item->getUrl())
                    ->map(fn (NavigationItem $item): NavigationItem => static::stripItemIcons($item))
                    ->values()
                    ->all();

                if ($items === []) {
                    return null;
                }

                return NavigationGroup::make($registered->getLabel())
                    ->icon($registered->getIcon())
                    ->items($items);
            })
            ->filter()
            ->values();

        $personalGroup = static::personalNavigationGroup($navigation, $clustersByPath);

        if ($personalGroup !== null) {
            $groups->prepend($personalGroup);
        }

        return $groups->all();
    }

    /**
     * Plugins register some items (each module's "Settings", approvals) under an English group
     * name that is not a registered group; they belong to the module group with the same meaning.
     *
     * @var array<string, string>
     */
    private const GROUP_ALIASES = [
        'project'       => 'admin.navigation.project',
        'inventory'     => 'admin.navigation.inventory',
        'invoices'      => 'admin.navigation.invoice',
        'manufacturing' => 'admin.navigation.manufacturing',
        'sales'         => 'admin.navigation.sale',
        'purchases'     => 'admin.navigation.purchase',
        'approvals'     => 'admin.navigation.approvals',
    ];

    private static function aliasKey(NavigationGroup $group): ?string
    {
        $label = strtolower((string) $group->getLabel());

        return array_key_exists($label, self::GROUP_ALIASES) && blank($group->getIcon()) ? $label : null;
    }

    /**
     * @param  array<NavigationGroup>  $navigation
     * @return array<int, NavigationItem>
     */
    private static function aliasedGroupItems(NavigationGroup $registered, array $navigation): array
    {
        return collect($navigation)
            ->filter(function (NavigationGroup $group) use ($registered): bool {
                $key = static::aliasKey($group);

                return $key !== null && __(self::GROUP_ALIASES[$key]) === $registered->getLabel();
            })
            ->flatMap(fn (NavigationGroup $group) => $group->getItems())
            ->values()
            ->all();
    }

    /**
     * @param  array<NavigationGroup>  $navigation
     */
    private static function matchNavigationGroup(NavigationGroup $registered, array $navigation): ?NavigationGroup
    {
        $icon = (string) $registered->getIcon();
        $label = $registered->getLabel();

        return collect($navigation)->first(
            fn (NavigationGroup $group): bool => $group->getIcon() === $icon,
        ) ?? collect($navigation)->first(
            fn (NavigationGroup $group): bool => $group->getLabel() === $label,
        );
    }

    /**
     * @param  Collection<string, class-string<Cluster>>  $clustersByPath
     * @return array<int, NavigationItem>
     */
    private static function flattenNavigationItem(NavigationItem $item, Collection $clustersByPath): array
    {
        $clusterChildren = static::clusterChildrenForItem($item, $clustersByPath);

        if ($clusterChildren->isNotEmpty()) {
            return $clusterChildren->all();
        }

        $existingChildren = collect($item->getChildItems() ?? [])
            ->filter(fn (NavigationItem $child): bool => $child->isVisible());

        if ($existingChildren->isNotEmpty()) {
            return $existingChildren
                ->flatMap(fn (NavigationItem $child): array => static::flattenNavigationItem($child, $clustersByPath))
                ->all();
        }

        return [$item];
    }

    /**
     * @param  Collection<string, class-string<Cluster>>  $clustersByPath
     * @return Collection<int, NavigationItem>
     */
    private static function clusterChildrenForItem(NavigationItem $item, Collection $clustersByPath): Collection
    {
        $itemPath = static::normalizePath($item->getUrl());

        if (blank($itemPath)) {
            return collect();
        }

        $clusterClass = $clustersByPath->get($itemPath);

        if ($clusterClass === null) {
            return collect();
        }

        return collect(static::navigationItemsForCluster($clusterClass))
            ->filter(fn (NavigationItem $child): bool => $child->isVisible());
    }

    /**
     * @return Collection<string, class-string<Cluster>>
     */
    private static function clusterIndex(): Collection
    {
        $panel = Filament::getCurrentPanel();

        if ($panel === null) {
            return collect();
        }

        return collect($panel->getPages())
            ->filter(fn (string $pageClass): bool => is_subclass_of($pageClass, Cluster::class))
            ->filter(fn (string $pageClass): bool => static::canAccessSafely($pageClass))
            ->mapWithKeys(function (string $pageClass): array {
                $path = static::normalizePath($pageClass::getUrl());

                return filled($path) ? [$path => $pageClass] : [];
            });
    }

    /**
     * @param  array<NavigationGroup>  $navigation
     */
    private static function personalNavigationGroup(array $navigation, Collection $clustersByPath): ?NavigationGroup
    {
        $items = collect($navigation)
            ->filter(fn (NavigationGroup $group): bool => blank($group->getLabel()) || blank($group->getIcon()))
            ->reject(fn (NavigationGroup $group): bool => static::aliasKey($group) !== null)
            ->flatMap(fn (NavigationGroup $group) => collect($group->getItems()))
            ->filter(fn (NavigationItem $item): bool => $item->isVisible())
            ->flatMap(fn (NavigationItem $item): array => static::flattenNavigationItem($item, $clustersByPath))
            ->unique(fn (NavigationItem $item): string => (string) $item->getUrl())
            ->map(fn (NavigationItem $item): NavigationItem => static::stripItemIcons($item))
            ->values()
            ->all();

        if ($items === []) {
            return null;
        }

        return NavigationGroup::make(__('navigation-settings.sidebar.personal'))
            ->icon('heroicon-o-user-circle')
            ->items($items);
    }

    /**
     * @param  class-string<Cluster>  $clusterClass
     * @return array<NavigationItem>
     */
    private static function navigationItemsForCluster(string $clusterClass): array
    {
        if (! $clusterClass::shouldRegisterSubNavigation()) {
            return [];
        }

        $items = [];

        foreach ($clusterClass::getClusteredComponents() as $component) {
            $isResourcePage = is_subclass_of($component, ResourcePage::class);

            $shouldRegisterNavigation = $isResourcePage
                ? $component::shouldRegisterNavigation()
                : $component::shouldRegisterNavigation();

            if (! $shouldRegisterNavigation) {
                continue;
            }

            if (! static::canAccessSafely($component)) {
                continue;
            }

            $componentItems = $isResourcePage
                ? $component::getNavigationItems()
                : $component::getNavigationItems();

            $items = [
                ...$items,
                ...$componentItems,
            ];
        }

        return $items;
    }

    private static function stripItemIcons(NavigationItem $item): NavigationItem
    {
        $stripped = NavigationItem::make($item->getLabel())
            ->url($item->getUrl())
            ->badge($item->getBadge(), $item->getBadgeColor())
            ->sort($item->getSort())
            ->isActiveWhen(fn (): bool => $item->isActive());

        if ($item->shouldOpenUrlInNewTab()) {
            $stripped->openUrlInNewTab();
        }

        return $stripped;
    }

    private static function normalizePath(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        return rtrim(parse_url($url, PHP_URL_PATH) ?? $url, '/');
    }

    /**
     * @param  class-string  $component
     */
    private static function canAccessSafely(string $component): bool
    {
        try {
            return $component::canAccess();
        } catch (\Throwable) {
            return false;
        }
    }
}
