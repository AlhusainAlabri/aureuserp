<?php

namespace App\Enums\Hr;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ClosureReason: string implements HasColor, HasIcon, HasLabel
{
    case Administrative = 'administrative';
    case Ethical = 'ethical';
    case Resignation = 'resignation';
    case Retirement = 'retirement';
    case ContractEnded = 'contract_ended';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('hr-extensions::employee.closure_reasons.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Administrative => 'gray',
            self::Ethical        => 'danger',
            self::Resignation    => 'warning',
            self::Retirement     => 'success',
            self::ContractEnded  => 'info',
            self::Other          => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Administrative => 'heroicon-o-document-text',
            self::Ethical        => 'heroicon-o-shield-exclamation',
            self::Resignation    => 'heroicon-o-arrow-right-on-rectangle',
            self::Retirement     => 'heroicon-o-sun',
            self::ContractEnded  => 'heroicon-o-calendar',
            self::Other          => 'heroicon-o-ellipsis-horizontal',
        };
    }
}
