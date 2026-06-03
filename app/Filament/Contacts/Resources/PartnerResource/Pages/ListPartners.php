<?php

namespace App\Filament\Contacts\Resources\PartnerResource\Pages;

use Filament\Actions\Action;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ListPartners as BaseListPartners;

class ListPartners extends BaseListPartners
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('configurations')
                ->label(__('contacts::filament/clusters/configurations.navigation.title'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(fn (): string => Configurations::getUrl())
                ->visible(fn (): bool => Configurations::canAccessClusteredComponents()),
            ...parent::getHeaderActions(),
        ];
    }
}
