<?php

namespace Webkul\Attendance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OvertimeDayType: string implements HasColor, HasLabel
{
    case Normal = 'normal';

    case Rest = 'rest';

    case Holiday = 'holiday';

    public function getLabel(): string
    {
        return __('attendances::enums.overtime_day_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Normal  => 'gray',
            self::Rest    => 'info',
            self::Holiday => 'warning',
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
