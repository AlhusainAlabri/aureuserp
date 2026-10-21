<?php

namespace App\Support\PluginManager;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Illuminate\Contracts\Support\Arrayable;

class PluginsNavigationManager extends NavigationManager
{
    /**
     * @return array<NavigationGroup>
     */
    public function get(): array
    {
        $groups = parent::get();

        if (! PluginManagerUi::isHidden()) {
            return $groups;
        }

        return self::withoutPluginGroups($groups);
    }

    /**
     * @param  array<NavigationGroup>  $groups
     * @return array<NavigationGroup>
     */
    public static function withoutPluginGroups(array $groups): array
    {
        return collect($groups)
            ->map(function (NavigationGroup $group): NavigationGroup {
                $items = collect($group->getItems())
                    ->map(function (NavigationItem $item): NavigationItem {
                        $children = collect($item->getChildItems())
                            ->reject(fn (NavigationItem $child): bool => PluginManagerUi::isPluginsPath($child->getUrl()))
                            ->all();

                        return $item->childItems($children);
                    })
                    ->reject(fn (NavigationItem $item): bool => PluginManagerUi::isPluginsPath($item->getUrl()))
                    ->values()
                    ->all();

                return $group->items($items);
            })
            ->filter(function (NavigationGroup $group): bool {
                $items = $group->getItems();

                if ($items instanceof Arrayable) {
                    return $items->count() > 0;
                }

                return $items !== [];
            })
            ->values()
            ->all();
    }
}
