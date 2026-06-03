<?php

namespace App\Filament\Concerns;

use App\Support\OmrFormatter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Models\Payslip;

trait EmployeePayslipHistoryRelation
{
    public function table(Table $table): Table
    {
        if (! Schema::hasTable('payroll_payslips')) {
            return $table
                ->emptyStateHeading(__('hr-extensions::payslip_history.plugin_not_installed'))
                ->columns([]);
        }

        return $table
            ->columns([
                TextColumn::make('period')
                    ->label(__('hr-extensions::payslip_history.fields.period'))
                    ->state(fn (Payslip $record): string => sprintf('%02d/%d', $record->period_month, $record->period_year))
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('period_year', $direction)
                        ->orderBy('period_month', $direction)),
                TextColumn::make('basic_salary')
                    ->label(__('hr-extensions::payslip_history.fields.basic_salary'))
                    ->formatStateUsing(fn (?string $state): string => OmrFormatter::format($state)),
                TextColumn::make('allowances')
                    ->label(__('hr-extensions::payslip_history.fields.allowances'))
                    ->state(fn (Payslip $record): string => OmrFormatter::format(max(0, (float) $record->gross_amount - (float) $record->basic_salary))),
                TextColumn::make('deductions_amount')
                    ->label(__('hr-extensions::payslip_history.fields.deductions'))
                    ->formatStateUsing(fn (?string $state): string => OmrFormatter::format($state)),
                TextColumn::make('net_amount')
                    ->label(__('hr-extensions::payslip_history.fields.net_salary'))
                    ->formatStateUsing(fn (?string $state): string => OmrFormatter::format($state)),
                TextColumn::make('notes')
                    ->label(__('hr-extensions::payslip_history.fields.notes'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('period_year', 'desc')
            ->paginated([12, 24, 48])
            ->emptyStateHeading(__('hr-extensions::payslip_history.empty_heading'))
            ->emptyStateDescription(__('hr-extensions::payslip_history.empty_description'));
    }
}
