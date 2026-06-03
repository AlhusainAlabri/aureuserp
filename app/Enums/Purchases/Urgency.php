<?php

namespace App\Enums\Purchases;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Urgency: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';

    public function getLabel(): string
    {
        return __('purchases-extensions::request.urgency.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low      => 'gray',
            self::Normal   => 'blue',
            self::High     => 'amber',
            self::Critical => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Low      => 'heroicon-o-arrow-down',
            self::Normal   => 'heroicon-o-minus',
            self::High     => 'heroicon-o-arrow-up',
            self::Critical => 'heroicon-o-exclamation-triangle',
        };
    }
}
