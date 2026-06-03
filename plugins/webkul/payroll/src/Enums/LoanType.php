<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LoanType: string implements HasColor, HasIcon, HasLabel
{
    case SalaryAdvance = 'salary_advance';

    case PersonalLoan = 'personal_loan';

    case EmergencyLoan = 'emergency_loan';

    case Other = 'other';

    public function getLabel(): string
    {
        return __('payroll::enums.loan_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SalaryAdvance => 'info',
            self::PersonalLoan  => 'primary',
            self::EmergencyLoan => 'warning',
            self::Other         => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SalaryAdvance => 'heroicon-o-wallet',
            self::PersonalLoan  => 'heroicon-o-credit-card',
            self::EmergencyLoan => 'heroicon-o-exclamation-triangle',
            self::Other         => 'heroicon-o-ellipsis-horizontal',
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
