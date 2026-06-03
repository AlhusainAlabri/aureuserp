<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasIcon, HasLabel
{
    case BankTransfer = 'bank_transfer';

    case Cash = 'cash';

    case Cheque = 'cheque';

    public function getLabel(): string
    {
        return __('payroll::enums.payment_method.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BankTransfer => 'info',
            self::Cash         => 'success',
            self::Cheque       => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::BankTransfer => 'heroicon-o-building-library',
            self::Cash         => 'heroicon-o-banknotes',
            self::Cheque       => 'heroicon-o-document-check',
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
