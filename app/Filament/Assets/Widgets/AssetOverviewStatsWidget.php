<?php

namespace App\Filament\Assets\Widgets;

use App\Filament\Assets\Concerns\InteractsWithAssetStats;
use App\Support\SchemaMemo;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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
            'sm'      => 2,
            'lg'      => 4,
        ];
    }

    protected function getStats(): array
    {
        if (! SchemaMemo::hasTable('assets')) {
            return [
                Stat::make(__('assets::assets.widgets.stats.unavailable'), '—')
                    ->description(__('assets::assets.widgets.stats.plugin_not_installed'))
                    ->color('gray'),
            ];
        }

        $pending = $this->countPendingRequests();
        $available = $this->countAvailableAssets();
        $borrowed = $this->countBorrowedAssets();
        $overdue = $this->countOverdueBorrowings();

        return [
            Stat::make(__('assets-extensions::dashboard.pending_requests'), $pending)
                ->description(__('assets-extensions::dashboard.view_pending'))
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color($pending > 0 ? 'warning' : 'success')
                ->url($this->pendingRequestsUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
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
                ->url($this->borrowedAssetsUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
