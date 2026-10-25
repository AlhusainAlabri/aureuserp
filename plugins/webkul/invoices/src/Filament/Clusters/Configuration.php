<?php

namespace Webkul\Invoice\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class Configuration extends Cluster
{
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?string $slug = 'invoices/configurations';

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('invoices::filament/clusters/configurations.navigation.group');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return static::getNavigationLabel();
    }
}
