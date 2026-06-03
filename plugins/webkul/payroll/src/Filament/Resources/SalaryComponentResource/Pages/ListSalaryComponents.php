<?php

namespace Webkul\Payroll\Filament\Resources\SalaryComponentResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;

class ListSalaryComponents extends ListRecords
{
    protected static string $resource = SalaryComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
