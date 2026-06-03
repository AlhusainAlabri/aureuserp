<?php

namespace Webkul\Project\Filament\Clusters;

use Filament\Clusters\Cluster;

/**
 * Overrides the projects plugin configurations cluster to keep it reachable
 * from project module pages while hiding it from the main navigation.
 */
class Configurations extends Cluster
{
    protected static ?string $slug = 'project/configurations';

    protected static ?int $navigationSort = 0;

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('projects::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('projects::filament/clusters/configurations.navigation.group');
    }
}
