<?php

namespace Webkul\Assets\Filament\Resources;

use App\Support\FilamentUrl;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\Assets\Enums\AssetCategory;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\CreateAsset;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\EditAsset;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\ListAssets;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\ViewAsset;
use Webkul\Assets\Filament\Resources\AssetResource\RelationManagers\BorrowingRelationManager;
use Webkul\Assets\Models\Asset;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 55;

    protected static ?string $slug = 'assets/assets';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('assets::assets.navigation.assets');
    }

    public static function getNavigationGroup(): string
    {
        return __('assets::assets.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('assets::assets.models.asset');
    }

    public static function getPluralModelLabel(): string
    {
        return __('assets::assets.navigation.assets');
    }

    public static function formatOmr(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        $prefix = app()->getLocale() === 'ar' ? 'ر.ع. ' : 'OMR ';

        return $prefix.number_format($value, 3);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'asset_number', 'serial_number', 'category'];
    }

    public static function isVehicleCategory(mixed $category): bool
    {
        if ($category instanceof AssetCategory) {
            return $category === AssetCategory::Vehicle;
        }

        return AssetCategory::tryFrom((string) $category) === AssetCategory::Vehicle;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('assets::assets.form.sections.details'))
                    ->schema([
                        Placeholder::make('asset_number')
                            ->label(__('assets::assets.fields.asset_number'))
                            ->content(fn (?Asset $record): string => $record?->asset_number ?? __('assets::assets.form.auto_generated')),
                        TextInput::make('name')
                            ->label(__('assets::assets.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category')
                            ->label(__('assets::assets.fields.category'))
                            ->options(AssetCategory::class)
                            ->searchable()
                            ->searchPrompt(__('filament-forms::components.select.search_prompt'))
                            ->live()
                            ->columnSpan(1),
                        TextInput::make('plate_number')
                            ->label(__('assets-extensions::fields.plate_number'))
                            ->maxLength(50)
                            ->visible(fn (Get $get): bool => static::isVehicleCategory($get('category')))
                            ->columnSpan(1),
                        TextInput::make('registration_number')
                            ->label(__('assets-extensions::fields.registration_number'))
                            ->maxLength(100)
                            ->visible(fn (Get $get): bool => static::isVehicleCategory($get('category')))
                            ->columnSpan(1),
                        TextInput::make('mileage')
                            ->label(__('assets-extensions::fields.mileage'))
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => static::isVehicleCategory($get('category')))
                            ->columnSpan(1),
                        TextInput::make('serial_number')
                            ->label(__('assets::assets.fields.serial_number'))
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('assets::assets.fields.status'))
                            ->options(AssetStatus::class)
                            ->default(AssetStatus::Available)
                            ->required(),
                        TextInput::make('value')
                            ->label(__('assets::assets.fields.value'))
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0),
                        TextInput::make('location')
                            ->label(__('assets::assets.fields.location'))
                            ->maxLength(255),
                        DatePicker::make('purchased_at')
                            ->label(__('assets::assets.fields.purchased_at'))
                            ->native(false),
                        Textarea::make('description')
                            ->label(__('assets::assets.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label(__('assets::assets.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_number')
                    ->label(__('assets::assets.fields.asset_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('assets::assets.fields.name'))
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('assets::assets.fields.category'))
                    ->formatStateUsing(function (?AssetCategory $state, Asset $record): string {
                        if ($state instanceof AssetCategory) {
                            return $state->getLabel();
                        }

                        return AssetCategory::tryFrom((string) $record->getRawOriginal('category'))?->getLabel() ?? '—';
                    })
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('assets::assets.fields.status'))
                    ->badge(),
                TextColumn::make('value')
                    ->label(__('assets::assets.fields.value'))
                    ->formatStateUsing(fn (?string $state): string => static::formatOmr($state !== null ? (float) $state : null))
                    ->sortable(),
                TextColumn::make('location')
                    ->label(__('assets::assets.fields.location'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('serial_number')
                    ->label(__('assets::assets.fields.serial_number'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activeBorrowing.employee.name')
                    ->label(__('assets::assets.fields.borrowed_by'))
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('assets::assets.fields.status'))
                    ->options(AssetStatus::class),
                SelectFilter::make('category')
                    ->label(__('assets::assets.fields.category'))
                    ->options(AssetCategory::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Asset $record): string => FilamentUrl::appendLocaleToUrl(
                        static::getUrl('view', ['record' => $record]),
                    )),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('asset_number', 'desc')
            ->recordUrl(fn (Asset $record): string => FilamentUrl::appendLocaleToUrl(
                static::getUrl('view', ['record' => $record]),
            ))
            ->emptyStateHeading(__('assets::assets.empty.no_assets'))
            ->emptyStateDescription(__('assets::assets.empty.no_assets_description'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('assets::assets.infolist.sections.details'))
                    ->schema([
                        TextEntry::make('asset_number')
                            ->label(__('assets::assets.fields.asset_number')),
                        TextEntry::make('name')
                            ->label(__('assets::assets.fields.name')),
                        TextEntry::make('category')
                            ->label(__('assets::assets.fields.category'))
                            ->formatStateUsing(fn (?AssetCategory $state): string => $state?->getLabel() ?? '—')
                            ->placeholder('—'),
                        TextEntry::make('status')
                            ->label(__('assets::assets.fields.status'))
                            ->badge(),
                        TextEntry::make('value')
                            ->label(__('assets::assets.fields.value'))
                            ->formatStateUsing(fn (?Asset $record): string => static::formatOmr($record->value !== null ? (float) $record->value : null))
                            ->placeholder('—'),
                        TextEntry::make('serial_number')
                            ->label(__('assets::assets.fields.serial_number'))
                            ->placeholder('—'),
                        TextEntry::make('location')
                            ->label(__('assets::assets.fields.location'))
                            ->placeholder('—'),
                        TextEntry::make('purchased_at')
                            ->label(__('assets::assets.fields.purchased_at'))
                            ->formatStateUsing(fn (?Asset $record): ?string => $record->purchased_at?->locale(app()->getLocale())->translatedFormat('j F Y'))
                            ->placeholder('—'),
                        TextEntry::make('activeBorrowing.employee.name')
                            ->label(__('assets::assets.fields.borrowed_by'))
                            ->placeholder('—'),
                        TextEntry::make('activeBorrowing.due_at')
                            ->label(__('assets::assets.fields.due_at'))
                            ->formatStateUsing(fn (?Asset $record): ?string => $record->activeBorrowing?->due_at
                                ? $record->activeBorrowing->due_at->locale(app()->getLocale())->translatedFormat('j F Y H:i')
                                : null)
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->label(__('assets::assets.fields.description'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label(__('assets::assets.fields.notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BorrowingRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'view'   => ViewAsset::route('/{record}'),
            'edit'   => EditAsset::route('/{record}/edit'),
        ];
    }
}
