<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SalaryComponentType: string implements HasColor, HasIcon, HasLabel
{
    case Earning = 'earning';

    case Deduction = 'deduction';

    case EmployerCost = 'employer_cost';

    public function getLabel(): string
    {
        return __('payroll::enums.salary_component_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Earning      => 'success',
            self::Deduction    => 'danger',
            self::EmployerCost => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Earning      => 'heroicon-o-arrow-up',
            self::Deduction    => 'heroicon-o-arrow-down',
            self::EmployerCost => 'heroicon-o-building-office-2',
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
