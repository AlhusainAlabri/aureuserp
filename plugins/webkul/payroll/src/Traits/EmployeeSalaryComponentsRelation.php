<?php

namespace Webkul\Payroll\Traits;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Models\SalaryComponent;

trait EmployeeSalaryComponentsRelation
{
    public function table(Table $table): Table
    {
        if (! Schema::hasTable('payroll_employee_components')) {
            return $table->columns([]);
        }

        return $table
            ->columns([
                TextColumn::make('component.code')
                    ->label(__('payroll::payroll.fields.code')),
                TextColumn::make('component.name')
                    ->label(__('payroll::payroll.fields.component'))
                    ->formatStateUsing(fn (?string $state, $record): string => $record->component?->display_name ?? (string) $state),
                TextColumn::make('amount')
                    ->label(__('payroll::payroll.fields.amount'))
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? SalaryComponentResource::formatMoney((float) $state) : '-'),
                TextColumn::make('percent')
                    ->label(__('payroll::payroll.fields.percent'))
                    ->suffix('%')
                    ->placeholder('-'),
                TextColumn::make('start_date')
                    ->label(__('payroll::payroll.fields.start_date'))
                    ->date(),
                TextColumn::make('end_date')
                    ->label(__('payroll::payroll.fields.end_date'))
                    ->date()
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => Schema::hasTable('payroll_employee_components'))
                    ->schema([
                        Select::make('component_id')
                            ->label(__('payroll::payroll.fields.component'))
                            ->options(fn (): array => SalaryComponent::query()->active()->orderBy('sort_order')->get()
                                ->mapWithKeys(fn (SalaryComponent $component): array => [$component->id => $component->display_name])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        TextInput::make('amount')
                            ->label(__('payroll::payroll.fields.amount'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => $this->assignmentUsesAmount((int) $get('component_id'))),
                        TextInput::make('percent')
                            ->label(__('payroll::payroll.fields.percent'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => $this->assignmentUsesPercent((int) $get('component_id'))),
                        DatePicker::make('start_date')
                            ->label(__('payroll::payroll.fields.start_date'))
                            ->required()
                            ->native(false),
                        DatePicker::make('end_date')
                            ->label(__('payroll::payroll.fields.end_date'))
                            ->native(false),
                        Textarea::make('notes')
                            ->label(__('payroll::payroll.fields.notes')),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema([
                        Select::make('component_id')
                            ->label(__('payroll::payroll.fields.component'))
                            ->options(fn (): array => SalaryComponent::query()->active()->orderBy('sort_order')->get()
                                ->mapWithKeys(fn (SalaryComponent $component): array => [$component->id => $component->display_name])
                                ->all())
                            ->required()
                            ->live(),
                        TextInput::make('amount')
                            ->label(__('payroll::payroll.fields.amount'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => $this->assignmentUsesAmount((int) $get('component_id'))),
                        TextInput::make('percent')
                            ->label(__('payroll::payroll.fields.percent'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => $this->assignmentUsesPercent((int) $get('component_id'))),
                        DatePicker::make('start_date')
                            ->label(__('payroll::payroll.fields.start_date'))
                            ->required()
                            ->native(false),
                        DatePicker::make('end_date')
                            ->label(__('payroll::payroll.fields.end_date'))
                            ->native(false),
                        Textarea::make('notes')
                            ->label(__('payroll::payroll.fields.notes')),
                    ]),
                DeleteAction::make(),
            ]);
    }

    protected function assignmentUsesAmount(?int $componentId): bool
    {
        if (! $componentId) {
            return true;
        }

        $component = SalaryComponent::query()->find($componentId);

        return $component && in_array($component->calculation_type, [CalculationType::Fixed, CalculationType::HoursBased], true);
    }

    protected function assignmentUsesPercent(?int $componentId): bool
    {
        if (! $componentId) {
            return false;
        }

        $component = SalaryComponent::query()->find($componentId);

        return $component && in_array($component->calculation_type, [CalculationType::PercentOfBasic, CalculationType::PercentOfGross], true);
    }
}
