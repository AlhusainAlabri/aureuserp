<?php

namespace Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource;

class EditEndOfServiceSettlement extends EditRecord
{
    protected static string $resource = EndOfServiceSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
