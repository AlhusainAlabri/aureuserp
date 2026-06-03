<?php

namespace App\Filament\Assets\Widgets;

use App\Filament\Assets\Concerns\InteractsWithAssetStats;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class AssetOverviewStatsWidget extends BaseWidget
{
    use InteractsWithAssetStats;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 3,
        ];
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('assets')) {
            return [
                Stat::make(__('assets::assets.widgets.stats.unavailable'), '—')
                    ->description(__('assets::assets.widgets.stats.plugin_not_installed'))
                    ->color('gray'),
            ];
        }

        $available = $this->countAvailableAssets();
        $borrowed = $this->countBorrowedAssets();
        $overdue = $this->countOverdueBorrowings();

        return [
            Stat::make(__('assets::assets.widgets.stats.available'), $available)
                ->description(__('assets::assets.widgets.stats.available_hint'))
                ->descriptionIcon('heroicon-m-check-circle', IconPosition::Before)
                ->color('success')
                ->url($this->availableAssetsUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
            Stat::make(__('assets::assets.widgets.stats.borrowed'), $borrowed)
                ->description(__('assets::assets.widgets.stats.borrowed_hint'))
                ->descriptionIcon('heroicon-m-arrow-right-circle', IconPosition::Before)
                ->color('warning')
                ->url($this->borrowedAssetsUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
            Stat::make(__('assets::assets.widgets.stats.overdue'), $overdue)
                ->description(__('assets::assets.widgets.stats.overdue_hint'))
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color($overdue > 0 ? 'danger' : 'gray')
                ->url($this->allAssetsUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
