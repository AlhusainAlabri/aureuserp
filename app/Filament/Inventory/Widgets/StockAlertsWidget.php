<?php

namespace App\Filament\Inventory\Widgets;

use App\Filament\Inventory\Concerns\InteractsWithInventoryStockCounts;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class StockAlertsWidget extends BaseWidget
{
    use InteractsWithInventoryStockCounts;

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 3;

    protected function getHeading(): ?string
    {
        return __('inventory-extensions::dashboard.out_of_stock');
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('inventories_order_points')) {
            return [
                Stat::make(__('inventory-extensions::dashboard.out_of_stock'), __('inventory-extensions::dashboard.plugin_missing'))
                    ->color('gray'),
            ];
        }

        $count = $this->countOutOfStock();

        return [
            Stat::make(__('inventory-extensions::dashboard.out_of_stock'), $count)
                ->description(__('inventory-extensions::dashboard.view_replenishment'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($count > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-archive-box-x-mark')
                ->url($this->replenishmentBelowMinimumUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
