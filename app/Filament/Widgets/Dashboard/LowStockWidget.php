<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Inventory\Concerns\InteractsWithInventoryStockCounts;
use App\Filament\Inventory\Concerns\InteractsWithPendingReceiptCount;
use App\Filament\Inventory\Pages\InventoryDashboard;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\Dashboard\DashboardMetricCache;
use App\Support\FilamentUrl;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class LowStockWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithInventoryStockCounts;
    use InteractsWithPageFilters;
    use InteractsWithPendingReceiptCount;

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.low_stock');
    }

    protected function getStats(): array
    {
        try {
            if (! Schema::hasTable('inventories_order_points')) {
                return [
                    Stat::make(__('dashboard.widgets.low_stock'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $counts = DashboardMetricCache::remember('low_stock', fn (): array => [
                'below_minimum'     => $this->countBelowMinimum(),
                'out_of_stock'      => $this->countOutOfStock(),
                'pending_receipts'  => $this->countPendingReceipts(),
            ]);

            $belowMinimum = $counts['below_minimum'];
            $outOfStock = $counts['out_of_stock'];
            $pendingReceipts = $counts['pending_receipts'];

            return [
                Stat::make(__('dashboard.stats.low_stock_items'), $belowMinimum)
                    ->description(__('dashboard.widgets.low_stock_description'))
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->color($belowMinimum > 0 ? 'danger' : 'success')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->url($this->replenishmentBelowMinimumUrl())
                    ->extraAttributes(['class' => 'cursor-pointer']),

                Stat::make(__('dashboard.stats.pending_receipts'), $pendingReceipts)
                    ->description(__('dashboard.widgets.missing_receipts'))
                    ->descriptionIcon('heroicon-m-truck')
                    ->color($pendingReceipts > 0 ? 'warning' : 'success')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->url($this->pendingReceiptsUrl())
                    ->extraAttributes(['class' => 'cursor-pointer']),

                Stat::make(__('dashboard.stats.out_of_stock_items'), $outOfStock)
                    ->description(__('inventory-extensions::dashboard.view_replenishment'))
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color($outOfStock > 0 ? 'danger' : 'success')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->url(FilamentUrl::appendLocaleToUrl(InventoryDashboard::getUrl()))
                    ->extraAttributes(['class' => 'cursor-pointer']),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.low_stock'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
