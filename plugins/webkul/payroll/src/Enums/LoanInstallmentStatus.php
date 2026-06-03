<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LoanInstallmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Scheduled = 'scheduled';

    case Deducted = 'deducted';

    case Skipped = 'skipped';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('payroll::enums.loan_installment_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scheduled => 'gray',
            self::Deducted  => 'success',
            self::Skipped   => 'warning',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Scheduled => 'heroicon-o-calendar',
            self::Deducted  => 'heroicon-o-check',
            self::Skipped   => 'heroicon-o-forward',
            self::Cancelled => 'heroicon-o-x-mark',
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
