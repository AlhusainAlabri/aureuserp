<?php

namespace Webkul\Payroll\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

class Configuration extends Cluster
{
    protected static ?int $navigationSort = 100;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'payroll/configuration';
    }

    public static function getNavigationLabel(): string
    {
        return __('payroll::payroll.navigation.config');
    }

    public static function getNavigationGroup(): string
    {
        return __('payroll::payroll.navigation.group');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('payroll::payroll.navigation.config');
    }
}
