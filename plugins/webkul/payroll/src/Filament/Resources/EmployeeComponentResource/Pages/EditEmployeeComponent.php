<?php

namespace Webkul\Payroll\Filament\Resources\EmployeeComponentResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Payroll\Filament\Resources\EmployeeComponentResource;

class EditEmployeeComponent extends EditRecord
{
    protected static string $resource = EmployeeComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
