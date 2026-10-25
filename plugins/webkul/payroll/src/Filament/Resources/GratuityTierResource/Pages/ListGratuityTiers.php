<?php

namespace Webkul\Payroll\Filament\Resources\GratuityTierResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Payroll\Filament\Resources\GratuityTierResource;

class ListGratuityTiers extends ListRecords
{
    protected static string $resource = GratuityTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
