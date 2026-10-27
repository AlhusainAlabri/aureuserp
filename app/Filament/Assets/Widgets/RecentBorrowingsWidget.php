<?php

namespace App\Filament\Assets\Widgets;

use App\Filament\Assets\Concerns\ConfiguresAssetBorrowingTable;
use App\Filament\Assets\Concerns\InteractsWithAssetStats;
use App\Support\SchemaMemo;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Webkul\Assets\Models\AssetBorrowing;

class RecentBorrowingsWidget extends BaseWidget
{
    use InteractsWithAssetStats;

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        return __('assets-extensions::dashboard.recent_activity');
    }

    public function table(Table $table): Table
    {
        if (! SchemaMemo::hasTable('asset_borrowings')) {
            return $table
                ->query(AssetBorrowing::query()->whereRaw('1 = 0'))
                ->columns([]);
        }

        return $table
            ->query(
                AssetBorrowing::query()
                    ->with(['asset', 'employee', 'borrowedBy', 'returnedBy'])
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('updated_at')
                    ->label(__('assets-extensions::dashboard.activity_at'))
                    ->formatStateUsing(fn ($state): ?string => ConfiguresAssetBorrowingTable::formatBorrowingDateTime($state))
                    ->sortable(),
                TextColumn::make('asset.name')
                    ->label(__('assets::assets.fields.name'))
                    ->wrap(),
                TextColumn::make('employee.name')
                    ->label(__('assets::assets.fields.employee'))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('assets::assets.fields.borrowing_status'))
                    ->badge(),
                TextColumn::make('borrowed_at')
                    ->label(__('assets::assets.fields.borrowed_at'))
                    ->formatStateUsing(fn ($state): ?string => ConfiguresAssetBorrowingTable::formatBorrowingDateTime($state))
                    ->toggleable(),
                TextColumn::make('returned_at')
                    ->label(__('assets::assets.fields.returned_at'))
                    ->formatStateUsing(fn ($state): ?string => ConfiguresAssetBorrowingTable::formatBorrowingDateTime($state))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('assets::assets.actions.view'))
                    ->url(fn (AssetBorrowing $record): string => $this->assetViewUrl($record->asset_id)),
            ])
            ->emptyStateHeading(__('assets-extensions::dashboard.no_recent_activity'))
            ->paginated(false);
    }
}
