<?php

namespace Webkul\Payroll\Filament\Resources\LoanResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('payroll::payroll.loan.installments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period')
                    ->label(__('payroll::payroll.fields.period'))
                    ->state(fn ($record): string => sprintf('%02d/%d', $record->period_month, $record->period_year)),
                TextColumn::make('amount')
                    ->label(__('payroll::payroll.fields.amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('status')
                    ->label(__('payroll::payroll.fields.status'))
                    ->badge(),
                TextColumn::make('payslip.reference_number')
                    ->label(__('payroll::payroll.fields.payslip'))
                    ->placeholder('-'),
                TextColumn::make('deducted_at')
                    ->label(__('payroll::payroll.fields.deducted_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->defaultSort('period_year');
    }
}
