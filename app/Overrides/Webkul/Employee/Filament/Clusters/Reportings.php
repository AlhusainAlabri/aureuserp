<?php

namespace Webkul\Employee\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

/**
 * Overrides the employees reportings cluster to keep skills reporting reachable
 * from employee module pages while hiding it from the main navigation.
 */
class Reportings extends Cluster
{
    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/reportings';
    }

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/clusters/reportings.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('employees::filament/clusters/reportings.navigation.group');
    }
}
