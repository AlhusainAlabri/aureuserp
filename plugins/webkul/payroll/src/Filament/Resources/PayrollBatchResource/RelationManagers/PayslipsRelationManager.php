<?php

namespace Webkul\Payroll\Filament\Resources\PayrollBatchResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Payroll\Filament\Resources\PayslipResource;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;

class PayslipsRelationManager extends RelationManager
{
    protected static string $relationship = 'payslips';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('payroll::payroll.relations.payslips');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('payroll::payroll.fields.reference_number'))
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label(__('payroll::payroll.fields.employee')),
                TextColumn::make('basic_salary')
                    ->label(__('payroll::payroll.fields.basic_salary'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('net_amount')
                    ->label(__('payroll::payroll.fields.net_amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('status')
                    ->label(__('payroll::payroll.fields.status'))
                    ->badge(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => PayslipResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
