<?php

namespace Webkul\Payroll\Filament\Resources\PayrollBatchResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource;

class EditPayrollBatch extends EditRecord
{
    protected static string $resource = PayrollBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
