<?php

namespace Webkul\Support\Traits;

use Filament\Pages\Enums\SubNavigationPosition;
use Webkul\Support\Filament\Widgets\RecordNavigationTabs;

trait HasRecordNavigationTabs
{
    protected function convertNavigationItemsToArray($navigationItems): array
    {
        return collect($navigationItems)
            ->reject(fn ($item): bool => $item->isHidden())
            ->map(fn ($item): array => [
                'label'      => $item->getLabel(),
                'url'        => $item->getUrl(),
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

        return [
            RecordNavigationTabs::make([
                'navigationItems' => $this->convertNavigationItemsToArray($navigationItems),
            ]),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return array_merge(
            $this->getRecordNavigationTabsWidget(),
            $this->getAdditionalHeaderWidgets()
        );
    }

    protected function getAdditionalHeaderWidgets(): array
    {
        return [];
    }

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Start;
    }
}
