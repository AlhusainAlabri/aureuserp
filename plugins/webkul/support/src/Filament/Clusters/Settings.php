<?php

namespace Webkul\Support\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class Settings extends Cluster
{
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?int $navigationSort = 1000;

    public static function getNavigationLabel(): string
    {
        return __('support::filament/clusters/settings/pages/settings.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('support::filament/clusters/settings/pages/settings.navigation.group');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return static::getNavigationLabel();
    }
}
