<?php

namespace Webkul\Payroll\Filament\Resources;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Filament\Clusters\Configuration;
use Webkul\Payroll\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages\CreatePasiRule;
use Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages\EditPasiRule;
use Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages\ListPasiRules;
use Webkul\Payroll\Models\PasiRule;

class PasiRuleResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = PasiRule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $slug = 'pasi-rules';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Configuration::class;

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
        return __('payroll::payroll.models.pasi_rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::payroll.models_plural.pasi_rule');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.pasi_rule'))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('category')
                            ->label(__('payroll::payroll.fields.pasi_category'))
                            ->options(PasiCategory::class)
                            ->required()
                            ->disabledOn('edit')
                            ->columnSpan(1),
                        TextInput::make('salary_cap')
                            ->label(__('payroll::payroll.fields.salary_cap'))
                            ->helperText(__('payroll::payroll.pasi.salary_cap_hint'))
                            ->numeric()
                            ->minValue(0.001)
                            ->columnSpan(1),
                        TextInput::make('employee_rate')
                            ->label(__('payroll::payroll.fields.employee_rate'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->columnSpan(1),
                        TextInput::make('employer_rate')
                            ->label(__('payroll::payroll.fields.employer_rate'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->columnSpan(1),
                        DatePicker::make('effective_from')
                            ->label(__('payroll::payroll.fields.effective_from'))
                            ->required()
                            ->columnSpan(1),
                        DatePicker::make('effective_to')
                            ->label(__('payroll::payroll.fields.effective_to'))
                            ->helperText(__('payroll::payroll.pasi.effective_to_hint'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit')
                            ->columnSpan(1),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->label(__('payroll::payroll.fields.pasi_category'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('employee_rate')
                    ->label(__('payroll::payroll.fields.employee_rate'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('employer_rate')
                    ->label(__('payroll::payroll.fields.employer_rate'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('salary_cap')
                    ->label(__('payroll::payroll.fields.salary_cap'))
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? SalaryComponentResource::formatMoney((float) $state) : __('payroll::payroll.pasi.no_cap')),
                TextColumn::make('effective_from')
                    ->label(__('payroll::payroll.fields.effective_from'))
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->label(__('payroll::payroll.fields.effective_to'))
                    ->date()
                    ->placeholder(__('payroll::payroll.pasi.open_ended'))
                    ->sortable(),
                IconColumn::make('is_current')
                    ->label(__('payroll::payroll.pasi.current'))
                    ->state(fn (PasiRule $record): bool => $record->effective_to === null)
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(__('payroll::payroll.fields.pasi_category'))
                    ->options(PasiCategory::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('effective_from', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPasiRules::route('/'),
            'create' => CreatePasiRule::route('/create'),
            'edit'   => EditPasiRule::route('/{record}/edit'),
        ];
    }
}
