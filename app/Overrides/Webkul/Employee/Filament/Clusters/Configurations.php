<?php

namespace Webkul\Employee\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

/**
 * Overrides the employees plugin configurations cluster to keep it reachable
 * from employee module pages while hiding it from the main navigation.
 */
class Configurations extends Cluster
{
    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('employees::filament/clusters/configurations.navigation.group');
    }
}
