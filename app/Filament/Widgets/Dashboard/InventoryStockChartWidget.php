<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Inventory\Concerns\InteractsWithInventoryStockCounts;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use App\Support\Inventory\InventoryStockCounter;
use App\Support\SchemaMemo;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class InventoryStockChartWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithInventoryStockCounts;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 25;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.charts.inventory_stock');
    }

    protected function getData(): array
    {
        if (! SchemaMemo::hasTable('inventories_order_points')) {
            return $this->emptyPluginData();
        }

        $belowMinimum = $this->countBelowMinimum();
        $outOfStock = $this->countOutOfStock();
        $ok = max(0, $this->countTotalSkus() - $belowMinimum - $outOfStock);

        return [
            'datasets' => [[
                'label'           => __('dashboard.charts.sku_count'),
                'data'            => [$ok, $belowMinimum, $outOfStock],
                'backgroundColor' => ['#22C55E', '#F97316', '#EF4444'],
            ]],
            'labels' => [
                __('dashboard.charts.stock_ok'),
                __('dashboard.charts.stock_low'),
                __('dashboard.charts.stock_out'),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function countTotalSkus(): int
    {
        return InventoryStockCounter::countTotalSkus();
    }

    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    protected function emptyPluginData(): array
    {
        return [
            'datasets' => [[
                'data'            => [1],
                'backgroundColor' => ['#9CA3AF'],
            ]],
            'labels' => [__('dashboard.plugin_not_installed')],
        ];
    }
}
