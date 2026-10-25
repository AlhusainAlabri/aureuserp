<?php

namespace Webkul\Attendance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DayStatus: string implements HasColor, HasLabel
{
    case Present = 'present';

    case Absent = 'absent';

    case Leave = 'leave';

    case Holiday = 'holiday';

    case Rest = 'rest';

    public function getLabel(): string
    {
        return __('attendances::enums.day_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Absent  => 'danger',
            self::Leave   => 'info',
            self::Holiday => 'warning',
            self::Rest    => 'gray',
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
