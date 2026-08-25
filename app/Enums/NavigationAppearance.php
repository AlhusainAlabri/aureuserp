<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationAppearance: string implements HasLabel
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

    public function getLabel(): string
    {
        return __('navigation-settings.menu_appearance.'.$this->value);
    }
}
