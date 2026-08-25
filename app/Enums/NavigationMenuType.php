<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationMenuType: string implements HasLabel
{
    case Sidebar = 'sidebar';
    case TopNavigation = 'top_navigation';
    case AppGrid = 'app_grid';

    public function getLabel(): string
    {
        return __('navigation-settings.menu_type.'.$this->value);
    }
}
