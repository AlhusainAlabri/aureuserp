<?php

namespace App\Enums\Operations;

use Filament\Support\Contracts\HasLabel;

enum BackupFrequency: string implements HasLabel
{
    case Disabled = 'disabled';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function getLabel(): string
    {
        return __('operations.settings.frequencies.'.$this->value);
    }
}
