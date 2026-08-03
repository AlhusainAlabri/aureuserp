<?php

namespace App\Support\Inventory;

use App\Support\Dashboard\DashboardMetricCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Models\OrderPoint;

class InventoryStockCounter
{
    /**
     * @return array{below_minimum: int, out_of_stock: int, total_skus: int}
     */
    public static function counts(): array
    {
        return DashboardMetricCache::remember('inventory.stock_counts', function (): array {
            return self::computeCounts();
        });
    }

    public static function countBelowMinimum(): int
    {
        return self::counts()['below_minimum'];
    }

    public static function countOutOfStock(): int
    {
        return self::counts()['out_of_stock'];
    }

    public static function countTotalSkus(): int
    {
        return self::counts()['total_skus'];
    }

    /**
     * @return array{below_minimum: int, out_of_stock: int, total_skus: int}
     */
    protected static function computeCounts(): array
    {
        if (! Schema::hasTable('inventories_order_points')) {
            return ['below_minimum' => 0, 'out_of_stock' => 0, 'total_skus' => 0];
        }

        $totalSkus = (int) OrderPoint::query()->count();

        if ($totalSkus === 0) {
            return ['below_minimum' => 0, 'out_of_stock' => 0, 'total_skus' => 0];
        }

        /** @var array<int, float> $onHandByProduct */
        $onHandByProduct = Schema::hasTable('inventories_product_quantities')
            ? DB::table('inventories_product_quantities')
                ->selectRaw('product_id, COALESCE(SUM(quantity), 0) as on_hand')
                ->groupBy('product_id')
                ->pluck('on_hand', 'product_id')
                ->map(fn (mixed $quantity): float => (float) $quantity)
                ->all()
            : [];

        $belowMinimum = 0;
        $outOfStock = 0;

        OrderPoint::query()
            ->select(['product_id', 'product_min_qty'])
            ->cursor()
            ->each(function (OrderPoint $point) use (&$belowMinimum, &$outOfStock, $onHandByProduct): void {
                $onHand = $onHandByProduct[$point->product_id] ?? 0.0;

                if ($onHand <= 0) {
                    $outOfStock++;
                }

                if ($onHand < (float) $point->product_min_qty) {
                    $belowMinimum++;
                }
            });

        return [
            'below_minimum' => $belowMinimum,
            'out_of_stock'  => $outOfStock,
            'total_skus'    => $totalSkus,
        ];
    }
}
