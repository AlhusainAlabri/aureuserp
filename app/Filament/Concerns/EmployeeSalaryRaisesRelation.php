<?php

namespace App\Filament\Concerns;

use App\Enums\Hr\RaiseReason;
use App\Models\Hr\EmployeeContract;
use App\Services\Hr\SalaryRaiseService;
use App\Support\OmrFormatter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

trait EmployeeSalaryRaisesRelation
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Hidden::make('creator_id')
                            ->default(fn () => Auth::id()),
                        Hidden::make('employee_id'),
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('effective_date')
                                    ->label(__('hr-extensions::salary_raise.fields.effective_date'))
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar'),
                                Select::make('reason')
                                    ->label(__('hr-extensions::salary_raise.fields.reason'))
                                    ->options(RaiseReason::class)
                                    ->required()
                                    ->native(false),
                                Select::make('contract_id')
                                    ->label(__('hr-extensions::salary_raise.fields.contract'))
                                    ->options(function ($livewire): array {
                                        if (! \Illuminate\Support\Facades\Schema::hasTable('employee_contracts')) {
                                            return [];
                                        }

                                        return EmployeeContract::query()
                                            ->where('employee_id', $livewire->getOwnerRecord()->id)
                                            ->orderByDesc('start_date')
                                            ->get()
                                            ->mapWithKeys(fn (EmployeeContract $contract): array => [
                                                $contract->id => $contract->start_date->format('Y-m-d').' — '.($contract->contract_type?->getLabel() ?? ''),
                                            ])
                                            ->all();
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->visible(fn (): bool => \Illuminate\Support\Facades\Schema::hasTable('employee_contracts')),
                                TextInput::make('old_amount')
                                    ->label(__('hr-extensions::salary_raise.fields.old_amount'))
                                    ->numeric()
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix(fn (): string => OmrFormatter::symbol())
                                    ->step(0.001),
                                TextInput::make('new_amount')
                                    ->label(__('hr-extensions::salary_raise.fields.new_amount'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix(fn (): string => OmrFormatter::symbol())
                                    ->step(0.001)
                                    ->live(onBlur: true),
                                Select::make('approved_by')
                                    ->label(__('hr-extensions::salary_raise.fields.approved_by'))
                                    ->relationship('approvedBy', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),
                        Textarea::make('notes')
                            ->label(__('hr-extensions::salary_raise.fields.notes'))
                            ->maxLength(2000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('approvedBy'))
            ->columns([
                TextColumn::make('effective_date')
                    ->label(__('hr-extensions::salary_raise.fields.effective_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('old_amount')
                    ->label(__('hr-extensions::salary_raise.fields.old_amount'))
                    ->formatStateUsing(fn (?string $state): string => OmrFormatter::format($state))
                    ->sortable(),
                TextColumn::make('new_amount')
                    ->label(__('hr-extensions::salary_raise.fields.new_amount'))
                    ->formatStateUsing(fn (?string $state): string => OmrFormatter::format($state))
                    ->sortable(),
                TextColumn::make('raise_amount')
                    ->label(__('hr-extensions::salary_raise.fields.raise_amount'))
                    ->formatStateUsing(fn (?string $state): string => OmrFormatter::format($state))
                    ->color(fn (?string $state): string => (float) $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('raise_percent')
                    ->label(__('hr-extensions::salary_raise.fields.raise_percent'))
                    ->formatStateUsing(fn (?string $state): string => number_format((float) $state, 2).'%'),
                TextColumn::make('reason')
                    ->label(__('hr-extensions::salary_raise.fields.reason'))
                    ->badge(),
                TextColumn::make('approvedBy.name')
                    ->label(__('hr-extensions::salary_raise.fields.approved_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('effective_date', 'desc')
            ->emptyStateHeading(__('hr-extensions::salary_raise.empty_heading'))
            ->emptyStateDescription(__('hr-extensions::salary_raise.empty_description'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('hr-extensions::salary_raise.actions.add'))
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $employeeId = $livewire->getOwnerRecord()->id;
                        $data['employee_id'] = $employeeId;
                        $data['old_amount'] ??= app(SalaryRaiseService::class)->resolveCurrentBasicAmount($employeeId);

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
