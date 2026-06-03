<?php

namespace Webkul\Payroll\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\LoanType;
use Webkul\Payroll\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Payroll\Filament\Resources\LoanResource\Pages\CreateLoan;
use Webkul\Payroll\Filament\Resources\LoanResource\Pages\EditLoan;
use Webkul\Payroll\Filament\Resources\LoanResource\Pages\ListLoans;
use Webkul\Payroll\Filament\Resources\LoanResource\Pages\ViewLoan;
use Webkul\Payroll\Filament\Resources\LoanResource\RelationManagers\InstallmentsRelationManager;
use Webkul\Payroll\Models\Loan;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\Infolists\ApprovalStatusSection;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class LoanResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = Loan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $slug = 'payroll/loans';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getNavigationGroup(): string
    {
        return __('payroll::payroll.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('payroll::payroll.models.loan');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::payroll.models_plural.loan');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.loan'))
                ->schema([
                    Grid::make(2)->schema([
                        Placeholder::make('reference_number')
                            ->label(__('payroll::payroll.fields.reference_number'))
                            ->content(fn (?Loan $record): string => $record?->reference_number ?? __('payroll::payroll.fields.auto_generated')),
                        Select::make('employee_id')
                            ->label(__('payroll::payroll.fields.employee'))
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        Select::make('loan_type')
                            ->label(__('payroll::payroll.fields.loan_type'))
                            ->options(LoanType::class)
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('total_amount')
                            ->label(__('payroll::payroll.fields.total_amount'))
                            ->numeric()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                $count = max((int) ($get('installment_count') ?: 1), 1);
                                $set('installment_amount', round((float) ($state ?? 0) / $count, 3));
                            })
                            ->columnSpan(1),
                        TextInput::make('installment_count')
                            ->label(__('payroll::payroll.fields.installment_count'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                $count = max((int) ($state ?: 1), 1);
                                $total = (float) ($get('total_amount') ?? 0);
                                $set('installment_amount', round($total / $count, 3));

                                $end = Loan::calculateEndPeriod(
                                    (int) ($get('start_period_year') ?: now()->year),
                                    (int) ($get('start_period_month') ?: now()->month),
                                    $count,
                                );
                                $set('end_period_year', $end['year']);
                                $set('end_period_month', $end['month']);
                            })
                            ->columnSpan(1),
                        TextInput::make('installment_amount')
                            ->label(__('payroll::payroll.fields.installment_amount'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                        TextInput::make('start_period_year')
                            ->label(__('payroll::payroll.fields.period_year'))
                            ->numeric()
                            ->required()
                            ->default(now()->year)
                            ->live(onBlur: true)
                            ->columnSpan(1),
                        TextInput::make('start_period_month')
                            ->label(__('payroll::payroll.fields.period_month'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->required()
                            ->default(now()->month)
                            ->live(onBlur: true)
                            ->columnSpan(1),
                        TextInput::make('end_period_year')
                            ->label(__('payroll::payroll.fields.end_period'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                        TextInput::make('end_period_month')
                            ->label(__('payroll::payroll.fields.period_month'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                        Textarea::make('reason')
                            ->label(__('payroll::payroll.fields.reason'))
                            ->required()
                            ->extraAttributes(['dir' => 'rtl'])
                            ->columnSpanFull(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('payroll::payroll.fields.reference_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label(__('payroll::payroll.fields.employee'))
                    ->searchable(),
                TextColumn::make('loan_type')
                    ->label(__('payroll::payroll.fields.loan_type'))
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label(__('payroll::payroll.fields.total_amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('installment_amount')
                    ->label(__('payroll::payroll.fields.installment_amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('amount_remaining')
                    ->label(__('payroll::payroll.fields.amount_remaining'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('status')
                    ->label(__('payroll::payroll.fields.status'))
                    ->badge(),
                ApprovalStatusColumn::make(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('payroll::payroll.filters.status'))
                    ->options(LoanStatus::class),
                SelectFilter::make('employee_id')
                    ->label(__('payroll::payroll.filters.employee'))
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Loan $record): bool => $record->status === LoanStatus::Draft),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.loan'))
                ->schema([
                    TextEntry::make('reference_number')->label(__('payroll::payroll.fields.reference_number')),
                    TextEntry::make('employee.name')->label(__('payroll::payroll.fields.employee')),
                    TextEntry::make('loan_type')->label(__('payroll::payroll.fields.loan_type'))->badge(),
                    TextEntry::make('status')->label(__('payroll::payroll.fields.status'))->badge(),
                    TextEntry::make('total_amount')
                        ->label(__('payroll::payroll.fields.total_amount'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('installment_count')->label(__('payroll::payroll.fields.installment_count')),
                    TextEntry::make('installment_amount')
                        ->label(__('payroll::payroll.fields.installment_amount'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('amount_repaid')
                        ->label(__('payroll::payroll.fields.amount_repaid'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('amount_remaining')
                        ->label(__('payroll::payroll.fields.amount_remaining'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('progress')
                        ->label(__('payroll::payroll.fields.progress'))
                        ->state(fn (Loan $record): string => $record->getProgressPercent().'%'),
                    TextEntry::make('reason')->label(__('payroll::payroll.fields.reason'))->columnSpanFull(),
                ])
                ->columns(2),
            ApprovalStatusSection::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make(__('payroll::payroll.relations.approvals'), [
                ApprovalsRelationManager::class,
            ]),
            RelationGroup::make(__('payroll::payroll.relations.installments'), [
                InstallmentsRelationManager::class,
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLoans::route('/'),
            'create' => CreateLoan::route('/create'),
            'view'   => ViewLoan::route('/{record}'),
            'edit'   => EditLoan::route('/{record}/edit'),
        ];
    }
}
