<?php

namespace Webkul\Payroll\Filament\Resources\SalaryComponentResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;

class ViewSalaryComponent extends ViewRecord
{
    protected static string $resource = SalaryComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
