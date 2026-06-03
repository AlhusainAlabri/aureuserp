<?php

namespace App\Enums\Hr;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TrainingStatus: string implements HasColor, HasIcon, HasLabel
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('hr-extensions::training.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planned    => 'gray',
            self::InProgress => 'warning',
            self::Completed  => 'success',
            self::Cancelled  => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Planned    => 'heroicon-o-clock',
            self::InProgress => 'heroicon-o-play',
            self::Completed  => 'heroicon-o-check-circle',
            self::Cancelled  => 'heroicon-o-x-circle',
        };
    }
}
