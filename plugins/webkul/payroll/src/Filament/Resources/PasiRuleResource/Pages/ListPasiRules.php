<?php

namespace Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Payroll\Filament\Resources\PasiRuleResource;

class ListPasiRules extends ListRecords
{
    protected static string $resource = PasiRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label(__('payroll::payroll.pasi.add_rate')),
        ];
    }
}
