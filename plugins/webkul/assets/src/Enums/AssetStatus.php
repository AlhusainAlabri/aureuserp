<?php

namespace Webkul\Assets\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssetStatus: string implements HasColor, HasIcon, HasLabel
{
    case Available = 'available';
    case Borrowed = 'borrowed';
    case Maintenance = 'maintenance';
    case Retired = 'retired';

    public function getLabel(): string
    {
        return __('assets::assets.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Available   => 'success',
            self::Borrowed    => 'warning',
            self::Maintenance => 'info',
            self::Retired     => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Available   => 'heroicon-o-check-circle',
            self::Borrowed    => 'heroicon-o-arrow-right-circle',
            self::Maintenance => 'heroicon-o-wrench-screwdriver',
            self::Retired     => 'heroicon-o-archive-box',
        };
    }
}
