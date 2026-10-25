<?php

namespace Webkul\Attendance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PunchSource: string implements HasColor, HasLabel
{
    case Self = 'self';

    case Device = 'device';

    case Manual = 'manual';

    public function getLabel(): string
    {
        return __('attendances::enums.punch_source.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Self   => 'info',
            self::Device => 'warning',
            self::Manual => 'gray',
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
