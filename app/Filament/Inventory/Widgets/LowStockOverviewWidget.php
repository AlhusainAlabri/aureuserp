<?php

namespace App\Filament\Inventory\Widgets;

use App\Filament\Inventory\Concerns\InteractsWithInventoryStockCounts;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class LowStockOverviewWidget extends BaseWidget
{
    use InteractsWithInventoryStockCounts;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 3;

    protected function getHeading(): ?string
    {
        return __('inventory-extensions::dashboard.low_stock');
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('inventories_order_points')) {
            return [
                Stat::make(__('inventory-extensions::dashboard.low_stock'), __('inventory-extensions::dashboard.plugin_missing'))
                    ->color('gray'),
            ];
        }

        $count = $this->countBelowMinimum();

        return [
            Stat::make(__('inventory-extensions::dashboard.low_stock'), $count)
                ->description(__('inventory-extensions::dashboard.view_replenishment'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($count > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->url($this->replenishmentBelowMinimumUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
