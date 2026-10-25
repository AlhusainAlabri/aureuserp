<?php

namespace Webkul\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SettlementStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';

    case PendingApproval = 'pending_approval';

    case Approved = 'approved';

    case Paid = 'paid';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('payroll::enums.settlement_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft           => 'gray',
            self::PendingApproval => 'warning',
            self::Approved        => 'info',
            self::Paid            => 'success',
            self::Cancelled       => 'danger',
        };
    }
}
