<?php

namespace Webkul\Purchase\Filament\Admin\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class Configurations extends Cluster
{
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?string $slug = 'purchase/configurations';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/admin/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('purchases::filament/admin/clusters/configurations.navigation.group');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return static::getNavigationLabel();
    }
}
