<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PayslipStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';

    case Validated = 'validated';

    case Paid = 'paid';

    public function getLabel(): string
    {
        return __('payroll::enums.payslip_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Validated => 'info',
            self::Paid      => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft     => 'heroicon-o-pencil',
            self::Validated => 'heroicon-o-check-circle',
            self::Paid      => 'heroicon-o-banknotes',
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
