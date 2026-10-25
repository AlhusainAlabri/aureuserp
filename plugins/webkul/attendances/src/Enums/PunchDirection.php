<?php

namespace Webkul\Attendance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PunchDirection: string implements HasColor, HasLabel
{
    case In = 'in';

    case Out = 'out';

    public function getLabel(): string
    {
        return __('attendances::enums.punch_direction.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::In  => 'success',
            self::Out => 'danger',
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
