<?php

namespace Webkul\Payroll\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Account\Models\Account;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Filament\Clusters\Configuration;
use Webkul\Payroll\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource\Pages\CreateSalaryComponent;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource\Pages\EditSalaryComponent;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource\Pages\ListSalaryComponents;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource\Pages\ViewSalaryComponent;
use Webkul\Payroll\Models\SalaryComponent;

class SalaryComponentResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = SalaryComponent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $slug = 'salary-components';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Configuration::class;

    protected static ?string $recordTitleAttribute = 'display_name';

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
        return __('payroll::payroll.models.salary_component');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::payroll.models_plural.salary_component');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.details'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('code')
                            ->label(__('payroll::payroll.fields.code'))
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),
                        TextInput::make('sort_order')
                            ->label(__('payroll::payroll.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->label(__('payroll::payroll.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('name_ar')
                            ->label(__('payroll::payroll.fields.name_ar'))
                            ->required()
                            ->maxLength(255)
                            ->extraAttributes(['dir' => 'rtl'])
                            ->columnSpan(1),
                        Select::make('type')
                            ->label(__('payroll::payroll.fields.type'))
                            ->options(SalaryComponentType::class)
                            ->required()
                            ->live()
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label(__('payroll::payroll.fields.is_active'))
                            ->default(true)
                            ->columnSpan(1),
                        Toggle::make('is_taxable')
                            ->label(__('payroll::payroll.fields.is_taxable'))
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
                ]),
            Section::make(__('payroll::payroll.form.sections.calculation'))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('calculation_type')
                            ->label(__('payroll::payroll.fields.calculation_type'))
                            ->options(CalculationType::class)
                            ->required()
                            ->live()
                            ->columnSpan(1),
                        TextInput::make('default_amount')
                            ->label(__('payroll::payroll.fields.default_amount'))
                            ->numeric()
                            ->suffix(__('payroll::payroll.currency.symbol_en'))
                            ->visible(fn (Get $get): bool => in_array($get('calculation_type'), ['fixed', 'hours_based'], true))
                            ->columnSpan(1),
                        TextInput::make('default_percent')
                            ->label(__('payroll::payroll.fields.default_percent'))
                            ->numeric()
                            ->suffix('%')
                            ->visible(fn (Get $get): bool => in_array($get('calculation_type'), ['percent_of_basic', 'percent_of_gross'], true))
                            ->columnSpan(1),
                        Textarea::make('formula')
                            ->label(__('payroll::payroll.fields.formula'))
                            ->visible(fn (Get $get): bool => $get('calculation_type') === 'formula')
                            ->columnSpanFull(),
                    ]),
                ]),
            Section::make(__('payroll::payroll.form.sections.accounting'))
                ->schema([
                    Select::make('account_id')
                        ->label(__('payroll::payroll.fields.account'))
                        ->options(fn (): array => static::accountOptions())
                        ->searchable()
                        ->preload()
                        ->visible(fn (): bool => class_exists(Account::class) && DbSchema::hasTable('accounts_accounts'))
                        ->columnSpanFull(),
                ])
                ->visible(fn (): bool => class_exists(Account::class) && DbSchema::hasTable('accounts_accounts')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('payroll::payroll.fields.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('payroll::payroll.fields.display_name'))
                    ->formatStateUsing(fn (?string $state, SalaryComponent $record): string => $record->display_name)
                    ->searchable(['name', 'name_ar'])
                    ->sortable(),
                TextColumn::make('name_ar')
                    ->label(__('payroll::payroll.fields.name_ar'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn (): bool => app()->getLocale() !== 'ar'),
                TextColumn::make('type')
                    ->label(__('payroll::payroll.fields.type'))
                    ->badge(),
                TextColumn::make('calculation_type')
                    ->label(__('payroll::payroll.fields.calculation_type'))
                    ->badge(),
                TextColumn::make('default_amount')
                    ->label(__('payroll::payroll.fields.default_amount'))
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? static::formatMoney((float) $state) : '-')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label(__('payroll::payroll.fields.sort_order'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('payroll::payroll.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('payroll::payroll.filters.type'))
                    ->options(SalaryComponentType::class),
                SelectFilter::make('calculation_type')
                    ->label(__('payroll::payroll.fields.calculation_type'))
                    ->options(CalculationType::class),
                TernaryFilter::make('is_active')
                    ->label(__('payroll::payroll.fields.is_active')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.details'))
                ->schema([
                    TextEntry::make('code')->label(__('payroll::payroll.fields.code')),
                    TextEntry::make('display_name')
                        ->label(fn (): string => app()->getLocale() === 'ar'
                            ? __('payroll::payroll.fields.name_ar')
                            : __('payroll::payroll.fields.name')),
                    TextEntry::make('name_ar')->label(__('payroll::payroll.fields.name_ar')),
                    TextEntry::make('type')->label(__('payroll::payroll.fields.type'))->badge(),
                    TextEntry::make('calculation_type')->label(__('payroll::payroll.fields.calculation_type'))->badge(),
                    TextEntry::make('default_amount')
                        ->label(__('payroll::payroll.fields.default_amount'))
                        ->formatStateUsing(fn (?string $state): string => $state !== null ? static::formatMoney((float) $state) : '-'),
                    TextEntry::make('default_percent')->label(__('payroll::payroll.fields.default_percent'))->suffix('%'),
                    IconEntry::make('is_taxable')->label(__('payroll::payroll.fields.is_taxable'))->boolean(),
                    IconEntry::make('is_active')->label(__('payroll::payroll.fields.is_active'))->boolean(),
                    TextEntry::make('account.name')
                        ->label(__('payroll::payroll.fields.account'))
                        ->visible(fn (): bool => class_exists(Account::class))
                        ->placeholder('-'),
                ])
                ->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSalaryComponents::route('/'),
            'create' => CreateSalaryComponent::route('/create'),
            'view'   => ViewSalaryComponent::route('/{record}'),
            'edit'   => EditSalaryComponent::route('/{record}/edit'),
        ];
    }

    public static function formatMoney(float $amount): string
    {
        $symbol = app()->getLocale() === 'ar'
            ? __('payroll::payroll.currency.symbol_ar')
            : __('payroll::payroll.currency.symbol_en');

        return $symbol.' '.number_format($amount, 3);
    }

    /**
     * @return array<int|string, string>
     */
    protected static function accountOptions(): array
    {
        if (! class_exists(Account::class) || ! DbSchema::hasTable('accounts_accounts')) {
            return [];
        }

        return Account::query()->pluck('name', 'id')->all();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('sort_order');
    }
}
