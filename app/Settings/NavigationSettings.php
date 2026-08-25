<?php

namespace App\Settings;

use App\Enums\NavigationAppearance;
use App\Enums\NavigationMenuType;
use App\Enums\SidebarDefaultState;
use Spatie\LaravelSettings\Settings;

class NavigationSettings extends Settings
{
    public NavigationMenuType $menu_type;

    public SidebarDefaultState $sidebar_default_state;

    public NavigationAppearance $menu_appearance;

    public static function group(): string
    {
        return 'navigation';
    }
}
