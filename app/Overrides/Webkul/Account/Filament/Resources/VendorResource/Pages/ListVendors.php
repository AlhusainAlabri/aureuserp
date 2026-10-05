<?php

namespace Webkul\Account\Filament\Resources\VendorResource\Pages;

use Filament\Actions\CreateAction;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Account\Filament\Resources\PartnerResource\Pages\ListPartners;
use Webkul\Account\Filament\Resources\VendorResource;

class ListVendors extends ListPartners
{
    protected static string $resource = VendorResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('accounts::filament/resources/vendor/pages/list-vendors.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('accounts::filament/resources/vendor/pages/list-vendors.header-actions.create.title'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
