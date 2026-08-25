<?php

namespace App\Support\Navigation;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Collection;

class SidebarNavigationGroups
{
    /**
     * @param  array<NavigationGroup>  $navigation
     * @return Collection<int, NavigationGroup>
     */
    public static function visibleGroups(array $navigation, bool $includeAllGroups): Collection
    {
        return collect($navigation)
            ->filter(function (NavigationGroup $group) use ($includeAllGroups): bool {
                if (static::visibleItems($group)->isEmpty()) {
                    return false;
                }

                if ($includeAllGroups) {
                    return true;
                }

                return $group->isActive();
            })
            ->values();
    }

    /**
     * @return Collection<int, NavigationItem>
     */
    public static function visibleItems(NavigationGroup $group): Collection
    {
        return collect($group->getItems())
            ->filter(fn (NavigationItem $item): bool => $item->isVisible());
    }
}
