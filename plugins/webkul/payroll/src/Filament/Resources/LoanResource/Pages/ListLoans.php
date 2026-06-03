<?php

namespace Webkul\Payroll\Filament\Resources\LoanResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Webkul\Payroll\Filament\Resources\LoanResource;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyStateHeading(__('payroll::payroll.table.empty'));
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
