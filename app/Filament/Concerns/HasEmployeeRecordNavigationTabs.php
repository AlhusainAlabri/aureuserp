<?php

namespace App\Filament\Concerns;

use App\Filament\Widgets\Employee\EmployeeRecordNavigationTabs;
use App\Support\FilamentUrl;
use Filament\Pages\Enums\SubNavigationPosition;
use Webkul\Support\Traits\HasRecordNavigationTabs;

trait HasEmployeeRecordNavigationTabs
{
    use HasRecordNavigationTabs;

    protected static function primaryEmployeeNavigationTabCount(): int
    {
        return 8;
    }

    protected function convertNavigationItemsToArray($navigationItems): array
    {
        return collect($navigationItems)
            ->reject(fn ($item): bool => $item->isHidden())
            ->map(fn ($item): array => [
                'label'      => $item->getLabel(),
                'url'        => FilamentUrl::appendLocaleToUrl($item->getUrl()),
                'isActive'   => $item->isActive(),
                'isHidden'   => $item->isHidden(),
                'icon'       => $item->getIcon(),
                'activeIcon' => $item->getactiveIcon(),
                'badge'      => $item->getBadge(),
                'badgeColor' => $item->getBadgeColor(),
            ])
            ->values()
            ->all();
    }

    protected function getRecordNavigationTabsWidget(): array
    {
        $navigationItems = static::getResource()::getRecordSubNavigation($this);
        $items = $this->convertNavigationItemsToArray($navigationItems);
        $primaryCount = static::primaryEmployeeNavigationTabCount();

        return [
            EmployeeRecordNavigationTabs::make([
                'primaryItems'  => array_slice($items, 0, $primaryCount),
                'overflowItems' => array_slice($items, $primaryCount),
            ]),
        ];
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Start;
    }
}
