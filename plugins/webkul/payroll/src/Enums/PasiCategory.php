<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PasiCategory: string implements HasColor, HasLabel
{
    case Omani = 'omani';

    case Expat = 'expat';

    public function getLabel(): string
    {
        return __('payroll::enums.pasi_category.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Omani => 'success',
            self::Expat => 'info',
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
