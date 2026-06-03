<?php

namespace App\Filament\Assets\Widgets;

use App\Filament\Assets\Concerns\InteractsWithAssetStats;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Models\AssetBorrowing;

class OverdueBorrowingsWidget extends BaseWidget
{
    use InteractsWithAssetStats;

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 6;

    public function getTableHeading(): ?string
    {
        return __('assets-extensions::dashboard.overdue');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('asset_borrowings')) {
            return $table
                ->query(AssetBorrowing::query()->whereRaw('1 = 0'))
                ->columns([]);
        }

        return $table
            ->query(
                AssetBorrowing::query()
                    ->with(['asset', 'employee'])
                    ->overdue()
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
                    ->dateTime('d M Y H:i'),
                TextColumn::make('days_overdue')
                    ->label(__('assets-extensions::dashboard.days_overdue'))
                    ->state(fn (AssetBorrowing $record): int => (int) $record->due_at?->diffInDays(now())),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('assets-extensions::dashboard.view_all_assets'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => $this->borrowedAssetsUrl())
                    ->color('gray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('assets::assets.actions.view'))
                    ->url(fn (AssetBorrowing $record): string => $this->assetViewUrl($record->asset_id)),
                Action::make('return')
                    ->label(__('assets::assets.actions.return'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->url(fn (AssetBorrowing $record): string => $this->assetViewUrl($record->asset_id)),
            ])
            ->emptyStateHeading(__('assets-extensions::dashboard.no_overdue'))
            ->paginated(false);
    }
}
