<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SidebarDefaultState: string implements HasLabel
{
    case Expanded = 'expanded';
    case Collapsed = 'collapsed';
    case Auto = 'auto';

    public function getLabel(): string
    {
        return __('navigation-settings.sidebar_default_state.'.$this->value);
    }
}
