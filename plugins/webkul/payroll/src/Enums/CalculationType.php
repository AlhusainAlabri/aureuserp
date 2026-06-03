<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CalculationType: string implements HasColor, HasIcon, HasLabel
{
    case Fixed = 'fixed';

    case PercentOfBasic = 'percent_of_basic';

    case PercentOfGross = 'percent_of_gross';

    case Formula = 'formula';

    case HoursBased = 'hours_based';

    public function getLabel(): string
    {
        return __('payroll::enums.calculation_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Fixed           => 'gray',
            self::PercentOfBasic  => 'info',
            self::PercentOfGross  => 'info',
            self::Formula         => 'warning',
            self::HoursBased      => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Fixed          => 'heroicon-o-equals',
            self::PercentOfBasic => 'heroicon-o-calculator',
            self::PercentOfGross => 'heroicon-o-chart-pie',
            self::Formula        => 'heroicon-o-code-bracket',
            self::HoursBased     => 'heroicon-o-clock',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->getLabel()])
            ->all();
    }
}
