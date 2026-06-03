<?php

namespace App\Filament\Concerns;

use App\Enums\Hr\ContractType;
use App\Support\OmrFormatter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait EmployeeContractsRelation
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Hidden::make('creator_id')
                            ->default(fn () => Auth::id()),
                        Grid::make(2)
                            ->schema([
                                Select::make('contract_type')
                                    ->label(__('hr-extensions::contract.fields.contract_type'))
                                    ->options(ContractType::class)
                                    ->default(ContractType::FixedTerm)
                                    ->required()
                                    ->native(false),
                                Toggle::make('is_active')
                                    ->label(__('hr-extensions::contract.fields.is_active'))
                                    ->default(true),
                                DatePicker::make('first_joining_date')
                                    ->label(__('hr-extensions::contract.fields.first_joining_date'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar'),
                                DatePicker::make('start_date')
                                    ->label(__('hr-extensions::contract.fields.start_date'))
                                    ->required()
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar'),
                                DatePicker::make('end_date')
                                    ->label(__('hr-extensions::contract.fields.end_date'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar'),
                                DatePicker::make('renewal_date')
                                    ->label(__('hr-extensions::contract.fields.renewal_date'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar'),
                                TextInput::make('wage')
                                    ->label(__('hr-extensions::contract.fields.wage'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.001)
                                    ->prefix(fn (): string => OmrFormatter::symbol()),
                            ]),
                        FileUpload::make('contract_file_path')
                            ->label(__('hr-extensions::contract.fields.contract_file'))
                            ->directory(fn ($livewire) => 'employees/'.$livewire->getOwnerRecord()->id.'/contracts')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label(__('hr-extensions::contract.fields.notes'))
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
            ->columns([
                TextColumn::make('contract_type')
                    ->label(__('hr-extensions::contract.fields.contract_type'))
                    ->badge(),
                TextColumn::make('start_date')
                    ->label(__('hr-extensions::contract.fields.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('hr-extensions::contract.fields.end_date'))
                    ->date()
                    ->color(fn ($record): ?string => $record->end_date?->isPast() ? 'danger' : ($record->end_date?->lte(now()->addDays(30)) ? 'warning' : null)),
                TextColumn::make('renewal_date')
                    ->label(__('hr-extensions::contract.fields.renewal_date'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('wage')
                    ->label(__('hr-extensions::contract.fields.wage'))
                    ->formatStateUsing(fn (?string $state): string => OmrFormatter::format($state)),
                IconColumn::make('is_active')
                    ->label(__('hr-extensions::contract.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('start_date', 'desc')
            ->emptyStateHeading(__('hr-extensions::contract.empty_heading'))
            ->emptyStateDescription(__('hr-extensions::contract.empty_description'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('hr-extensions::contract.actions.add')),
            ])
            ->recordActions([
                Action::make('viewContract')
                    ->label(__('hr-extensions::contract.actions.view_file'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record): ?string => $record->contractTemporaryUrl())
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => filled($record->contract_file_path)
                        && Storage::disk('private')->exists($record->contract_file_path)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
