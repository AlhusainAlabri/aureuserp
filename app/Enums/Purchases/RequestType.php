<?php

namespace App\Enums\Purchases;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RequestType: string implements HasColor, HasIcon, HasLabel
{
    case StandardPurchase = 'standard_purchase';
    case DeviceRequest = 'device_request';
    case TechnicalSupport = 'technical_support';
    case OfficeSupplies = 'office_supplies';
    case Maintenance = 'maintenance';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('purchases-extensions::request.types.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::StandardPurchase => 'gray',
            self::DeviceRequest    => 'blue',
            self::TechnicalSupport => 'purple',
            self::OfficeSupplies   => 'teal',
            self::Maintenance      => 'amber',
            self::Other            => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::StandardPurchase => 'heroicon-o-shopping-cart',
            self::DeviceRequest    => 'heroicon-o-computer-desktop',
            self::TechnicalSupport => 'heroicon-o-wrench-screwdriver',
            self::OfficeSupplies   => 'heroicon-o-paper-clip',
            self::Maintenance      => 'heroicon-o-cog-6-tooth',
            self::Other            => 'heroicon-o-ellipsis-horizontal',
        };
    }

    /** @return list<string> */
    public static function internalRequestTypes(): array
    {
        return [
            self::DeviceRequest->value,
            self::TechnicalSupport->value,
            self::OfficeSupplies->value,
            self::Maintenance->value,
            self::Other->value,
        ];
    }
}
