<?php

namespace App\Enums\Hr;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RaiseReason: string implements HasColor, HasIcon, HasLabel
{
    case AnnualReview = 'annual_review';
    case Performance = 'performance';
    case Promotion = 'promotion';
    case CostOfLiving = 'cost_of_living';
    case MarketAdjustment = 'market_adjustment';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('hr-extensions::salary_raise.reasons.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AnnualReview     => 'blue',
            self::Performance      => 'success',
            self::Promotion        => 'purple',
            self::CostOfLiving     => 'amber',
            self::MarketAdjustment => 'teal',
            self::Other            => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::AnnualReview     => 'heroicon-o-calendar-days',
            self::Performance      => 'heroicon-o-star',
            self::Promotion        => 'heroicon-o-arrow-trending-up',
            self::CostOfLiving     => 'heroicon-o-banknotes',
            self::MarketAdjustment => 'heroicon-o-chart-bar',
            self::Other            => 'heroicon-o-ellipsis-horizontal',
        };
    }
}
