<?php

namespace Webkul\Contact\Filament\Clusters;

use Filament\Clusters\Cluster;

/**
 * Overrides the contacts plugin cluster to keep configurations accessible
 * from the Contacts list page while hiding it from the main navigation.
 */
class Configurations extends Cluster
{
    protected static ?string $slug = 'contact/configurations';

    protected static ?int $navigationSort = 0;

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('contacts::filament/clusters/configurations.navigation.group');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return static::getNavigationLabel();
    }
}
