<?php

namespace Webkul\Payroll\Filament\Resources\SalaryComponentResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;

class EditSalaryComponent extends EditRecord
{
    protected static string $resource = SalaryComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
