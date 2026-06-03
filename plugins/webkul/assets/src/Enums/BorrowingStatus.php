<?php

namespace Webkul\Assets\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BorrowingStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Returned = 'returned';
    case Overdue = 'overdue';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return __('assets::assets.borrowing_statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending, self::PendingApproval   => 'warning',
            self::Active                           => 'info',
            self::Returned                         => 'success',
            self::Overdue                          => 'danger',
            self::Rejected                         => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending, self::PendingApproval   => 'heroicon-o-clock',
            self::Active                           => 'heroicon-o-arrow-right-circle',
            self::Returned                         => 'heroicon-o-check-circle',
            self::Overdue                          => 'heroicon-o-exclamation-triangle',
            self::Rejected                         => 'heroicon-o-x-circle',
        };
    }
}
