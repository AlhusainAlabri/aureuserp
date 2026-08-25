<?php

namespace App\Filament\Inventory\Widgets;

use App\Filament\Inventory\Concerns\InteractsWithInventoryStockCounts;
use App\Filament\Inventory\Concerns\InteractsWithPendingReceiptCount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class InventoryOverviewWidget extends BaseWidget
{
    use InteractsWithInventoryStockCounts;
    use InteractsWithPendingReceiptCount;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (! Schema::hasTable('inventories_order_points')) {
            return [
                Stat::make(__('inventory-extensions::dashboard.low_stock'), __('inventory-extensions::dashboard.plugin_missing'))
                    ->color('gray'),
            ];
        }

        $belowMinimum = $this->countBelowMinimum();
        $outOfStock = $this->countOutOfStock();
        $pendingReceipts = Schema::hasTable('purchases_orders') && Schema::hasTable('purchases_order_operations')
            ? $this->countPendingReceipts()
            : 0;

        return [
            Stat::make(__('inventory-extensions::dashboard.low_stock'), $belowMinimum)
                ->description(__('inventory-extensions::dashboard.view_replenishment'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($belowMinimum > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->url($this->replenishmentBelowMinimumUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make(__('inventory-extensions::dashboard.pending_receipts'), $pendingReceipts)
                ->description(__('inventory-extensions::dashboard.view_receipts'))
                ->descriptionIcon('heroicon-m-truck')
                ->color($pendingReceipts > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-inbox-arrow-down')
                ->url($this->pendingReceiptsUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make(__('inventory-extensions::dashboard.out_of_stock'), $outOfStock)
                ->description(__('inventory-extensions::dashboard.view_replenishment'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStock > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-archive-box-x-mark')
                ->url($this->replenishmentBelowMinimumUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
