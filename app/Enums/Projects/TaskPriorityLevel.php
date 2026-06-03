<?php

namespace App\Enums\Projects;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskPriorityLevel: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function getLabel(): string
    {
        return __('tasks.priority.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low    => 'gray',
            self::Medium => 'info',
            self::High   => 'warning',
            self::Urgent => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Low    => 'heroicon-o-arrow-down',
            self::Medium => 'heroicon-o-minus',
            self::High   => 'heroicon-o-arrow-up',
            self::Urgent => 'heroicon-o-exclamation-triangle',
        };
    }
}
