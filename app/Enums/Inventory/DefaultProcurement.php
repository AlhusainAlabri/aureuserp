<?php

namespace App\Enums\Inventory;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DefaultProcurement: string implements HasColor, HasIcon, HasLabel
{
    case InternalRequest = 'internal_request';
    case DraftPo = 'draft_po';

    public function getLabel(): string
    {
        return __('inventory-extensions::procurement.types.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InternalRequest => 'info',
            self::DraftPo         => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::InternalRequest => 'heroicon-o-document-text',
            self::DraftPo         => 'heroicon-o-shopping-cart',
        };
    }
}
