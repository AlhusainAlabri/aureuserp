<?php

namespace App\Enums\Hr;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TrainingType: string implements HasColor, HasIcon, HasLabel
{
    case Internal = 'internal';
    case External = 'external';
    case Online = 'online';
    case Workshop = 'workshop';
    case Conference = 'conference';
    case Certification = 'certification';

    public function getLabel(): string
    {
        return __('hr-extensions::training.types.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Internal      => 'blue',
            self::External      => 'purple',
            self::Online        => 'teal',
            self::Workshop      => 'amber',
            self::Conference    => 'pink',
            self::Certification => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Internal      => 'heroicon-o-building-office',
            self::External      => 'heroicon-o-arrow-top-right-on-square',
            self::Online        => 'heroicon-o-globe-alt',
            self::Workshop      => 'heroicon-o-wrench-screwdriver',
            self::Conference    => 'heroicon-o-presentation-chart-bar',
            self::Certification => 'heroicon-o-academic-cap',
        };
    }
}
