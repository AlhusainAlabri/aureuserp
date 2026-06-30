<?php

namespace App\Filament\Assets\Resources;

use App\Filament\Assets\Concerns\ConfiguresAssetBorrowingTable;
use App\Filament\Assets\Pages\PendingBorrowingRequests;
use App\Filament\Assets\Resources\AssetBorrowingResource\Pages\ViewAssetBorrowing;
use App\Filament\Infolists\ApprovalStatusSection;
use App\Services\Assets\AssetSignatureStorageService;
use App\Support\FilamentUrl;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Assets\Models\AssetBorrowing;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class AssetBorrowingResource extends Resource
{
    use ConfiguresAssetBorrowingTable;

    protected static ?string $model = AssetBorrowing::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 56;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'assets/borrowings';

    public static function getNavigationLabel(): string
    {
        return __('assets-extensions::navigation.borrowings');
    }

    public static function getNavigationGroup(): string
    {
        return __('assets::assets.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('assets-extensions::models.borrowing');
    }

    public static function getPluralModelLabel(): string
    {
        return __('assets-extensions::navigation.borrowings');
    }

    public static function canAccess(): bool
    {
        return DbSchema::hasTable('asset_borrowings');
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return FilamentUrl::appendLocaleToUrl(
            PendingBorrowingRequests::getUrl($parameters, $isAbsolute, $panel, $tenant),
        );
    }

    public static function table(Table $table): Table
    {
        $instance = new static;

        return $instance->configureBorrowingTable(
            $table->query(fn (): Builder => AssetBorrowing::query()->with(['asset', 'employee', 'requestedBy']))
        );
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ApprovalStatusSection::make(),
                Section::make(__('assets-extensions::infolist.details'))
                    ->schema([
                        TextEntry::make('asset.asset_number')
                            ->label(__('assets::assets.fields.asset_number')),
                        TextEntry::make('asset.name')
                            ->label(__('assets::assets.fields.name')),
                        TextEntry::make('employee.name')
                            ->label(__('assets::assets.fields.employee'))
                            ->placeholder('—'),
                        TextEntry::make('status')
                            ->label(__('assets::assets.fields.borrowing_status'))
                            ->badge(),
                        TextEntry::make('due_at')
                            ->label(__('assets::assets.fields.due_at'))
                            ->formatStateUsing(fn (AssetBorrowing $record): ?string => ConfiguresAssetBorrowingTable::formatBorrowingDateTime($record->due_at))
                            ->placeholder('—'),
                        TextEntry::make('borrowed_at')
                            ->label(__('assets::assets.fields.borrowed_at'))
                            ->formatStateUsing(fn (AssetBorrowing $record): ?string => ConfiguresAssetBorrowingTable::formatBorrowingDateTime($record->borrowed_at))
                            ->placeholder('—'),
                        TextEntry::make('returned_at')
                            ->label(__('assets::assets.fields.returned_at'))
                            ->formatStateUsing(fn (AssetBorrowing $record): ?string => ConfiguresAssetBorrowingTable::formatBorrowingDateTime($record->returned_at))
                            ->placeholder('—'),
                        TextEntry::make('rejection_reason')
                            ->label(__('assets-extensions::fields.rejection_reason'))
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->visible(fn (AssetBorrowing $record): bool => $record->rejection_reason !== null),
                        TextEntry::make('notes')
                            ->label(__('assets::assets.fields.notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('assets-extensions::signatures.section'))
                    ->schema([
                        ImageEntry::make('borrow_signature_url')
                            ->label(__('assets-extensions::signatures.borrow'))
                            ->state(fn (AssetBorrowing $record): ?string => app(AssetSignatureStorageService::class)->temporaryUrl($record->borrow_signature_path))
                            ->visible(fn (AssetBorrowing $record): bool => $record->borrow_signature_path !== null),
                        ImageEntry::make('return_signature_url')
                            ->label(__('assets-extensions::signatures.return'))
                            ->state(fn (AssetBorrowing $record): ?string => app(AssetSignatureStorageService::class)->temporaryUrl($record->return_signature_path))
                            ->visible(fn (AssetBorrowing $record): bool => $record->return_signature_path !== null),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ApprovalsRelationManager::class,
            AssetBorrowingResource\RelationManagers\EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewAssetBorrowing::route('/{record}'),
        ];
    }
}
