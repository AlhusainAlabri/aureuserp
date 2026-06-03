<?php

namespace App\Enums\Hr;

use Filament\Support\Contracts\HasLabel;

enum ContractType: string implements HasLabel
{
    case Permanent = 'permanent';
    case FixedTerm = 'fixed_term';
    case Temporary = 'temporary';
    case Probation = 'probation';

    public function getLabel(): string
    {
        return __('hr-extensions::contract.types.'.$this->value);
    }
}
