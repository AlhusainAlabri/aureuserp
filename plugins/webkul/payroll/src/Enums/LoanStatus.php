<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LoanStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';

    case PendingApproval = 'pending_approval';

    case Approved = 'approved';

    case Active = 'active';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('payroll::enums.loan_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft           => 'gray',
            self::PendingApproval => 'warning',
            self::Approved        => 'info',
            self::Active          => 'success',
            self::Completed       => 'success',
            self::Cancelled       => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft           => 'heroicon-o-pencil',
            self::PendingApproval => 'heroicon-o-clock',
            self::Approved        => 'heroicon-o-check',
            self::Active          => 'heroicon-o-credit-card',
            self::Completed       => 'heroicon-o-check-circle',
            self::Cancelled       => 'heroicon-o-x-mark',
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
