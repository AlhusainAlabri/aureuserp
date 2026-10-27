<?php

namespace App\Filament\Assets\Widgets;

use App\Filament\Assets\Concerns\ConfiguresAssetBorrowingTable;
use App\Filament\Assets\Concerns\InteractsWithAssetStats;
use App\Support\SchemaMemo;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Models\AssetBorrowing;

class DueSoonBorrowingsWidget extends BaseWidget
{
    use InteractsWithAssetStats;

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 6;

    public function getTableHeading(): ?string
    {
        return __('assets-extensions::dashboard.due_soon');
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
                    ->with(['asset', 'employee'])
                    ->where('status', BorrowingStatus::Active)
                    ->whereBetween('due_at', [now(), now()->addDays(7)])
                    ->orderBy('due_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('asset.name')
                    ->label(__('assets::assets.fields.name'))
                    ->wrap(),
                TextColumn::make('employee.name')
                    ->label(__('assets::assets.fields.employee'))
                    ->placeholder('—'),
                TextColumn::make('due_at')
                    ->label(__('assets::assets.fields.due_at'))
                    ->formatStateUsing(fn ($state): ?string => ConfiguresAssetBorrowingTable::formatBorrowingDateTime($state))
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('assets-extensions::dashboard.view_borrowed_assets'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => $this->borrowedAssetsUrl())
                    ->color('gray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('assets::assets.actions.view'))
                    ->url(fn (AssetBorrowing $record): string => $this->assetViewUrl($record->asset_id)),
            ])
            ->emptyStateHeading(__('assets-extensions::dashboard.no_due_soon'))
            ->paginated(false);
    }
}
