<?php

namespace App\Support\Inventory;

use App\Support\Dashboard\DashboardMetricCache;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Product;

class InventoryStockCounter
{
    /**
     * @return array{below_minimum: int, out_of_stock: int}
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

    /**
     * @return array{below_minimum: int, out_of_stock: int}
     */
    protected static function computeCounts(): array
    {
        if (! Schema::hasTable('inventories_order_points')) {
            return ['below_minimum' => 0, 'out_of_stock' => 0];
        }

        $points = OrderPoint::query()
            ->select(['id', 'product_id', 'product_min_qty'])
            ->get();

        if ($points->isEmpty()) {
            return ['below_minimum' => 0, 'out_of_stock' => 0];
        }

        $products = Product::query()
            ->whereIn('id', $points->pluck('product_id')->unique()->filter())
            ->get()
            ->keyBy('id');

        $belowMinimum = 0;
        $outOfStock = 0;

        foreach ($points as $point) {
            $product = $products->get($point->product_id);

            if (! $product) {
                continue;
            }

            $available = (float) $product->available_qty;

            if ($available <= 0) {
                $outOfStock++;
            }

            if ($available < (float) $point->product_min_qty) {
                $belowMinimum++;
            }
        }

        return [
            'below_minimum' => $belowMinimum,
            'out_of_stock'  => $outOfStock,
        ];
    }
}
