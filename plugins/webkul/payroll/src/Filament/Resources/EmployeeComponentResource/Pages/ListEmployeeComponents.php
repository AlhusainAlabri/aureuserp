<?php

namespace Webkul\Payroll\Filament\Resources\EmployeeComponentResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Webkul\Payroll\Filament\Resources\EmployeeComponentResource;

class ListEmployeeComponents extends ListRecords
{
    protected static string $resource = EmployeeComponentResource::class;

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
