<?php

namespace Webkul\Assets\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssetCategory: string implements HasColor, HasIcon, HasLabel
{
    case Vehicle = 'vehicle';
    case Furniture = 'furniture';
    case Equipment = 'equipment';

    public function getLabel(): string
    {
        return __('assets-extensions::categories.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Vehicle   => 'blue',
            self::Furniture => 'amber',
            self::Equipment => 'purple',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Vehicle   => 'heroicon-o-truck',
            self::Furniture => 'heroicon-o-home-modern',
            self::Equipment => 'heroicon-o-wrench-screwdriver',
        };
    }
}
