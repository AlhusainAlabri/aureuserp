<?php

namespace Webkul\Payroll\Filament\Resources;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Payroll\Filament\Clusters\Configuration;
use Webkul\Payroll\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Payroll\Filament\Resources\GratuityTierResource\Pages\CreateGratuityTier;
use Webkul\Payroll\Filament\Resources\GratuityTierResource\Pages\EditGratuityTier;
use Webkul\Payroll\Filament\Resources\GratuityTierResource\Pages\ListGratuityTiers;
use Webkul\Payroll\Models\GratuityTier;

class GratuityTierResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = GratuityTier::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $slug = 'gratuity-tiers';

    protected static ?int $navigationSort = 3;

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
        return __('payroll::payroll.models.gratuity_tier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::payroll.models_plural.gratuity_tier');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.gratuity_tier'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('from_year')
                            ->label(__('payroll::payroll.fields.from_year'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->columnSpan(1),
                        TextInput::make('to_year')
                            ->label(__('payroll::payroll.fields.to_year'))
                            ->helperText(__('payroll::payroll.gratuity.to_year_hint'))
                            ->numeric()
                            ->minValue(0)
                            ->gt('from_year')
                            ->columnSpan(1),
                        TextInput::make('days_per_year')
                            ->label(__('payroll::payroll.fields.days_per_year'))
                            ->helperText(__('payroll::payroll.gratuity.days_per_year_hint'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(365)
                            ->columnSpanFull(),
                        DatePicker::make('effective_from')
                            ->label(__('payroll::payroll.fields.effective_from'))
                            ->required()
                            ->columnSpan(1),
                        DatePicker::make('effective_to')
                            ->label(__('payroll::payroll.fields.effective_to'))
                            ->helperText(__('payroll::payroll.gratuity.effective_to_hint'))
                            ->afterOrEqual('effective_from')
                            ->columnSpan(1),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_year')
                    ->label(__('payroll::payroll.fields.from_year'))
                    ->sortable(),
                TextColumn::make('to_year')
                    ->label(__('payroll::payroll.fields.to_year'))
                    ->placeholder(__('payroll::payroll.gratuity.open_ended'))
                    ->sortable(),
                TextColumn::make('days_per_year')
                    ->label(__('payroll::payroll.fields.days_per_year'))
                    ->sortable(),
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
                    ->state(fn (GratuityTier $record): bool => $record->effective_to === null)
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('effective_from', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGratuityTiers::route('/'),
            'create' => CreateGratuityTier::route('/create'),
            'edit'   => EditGratuityTier::route('/{record}/edit'),
        ];
    }
}
