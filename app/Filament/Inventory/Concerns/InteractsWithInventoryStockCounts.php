<?php

namespace App\Filament\Inventory\Concerns;

use App\Support\FilamentUrl;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource;
use Webkul\Inventory\Models\OrderPoint;

trait InteractsWithInventoryStockCounts
{
    protected function countBelowMinimum(): int
    {
        if (! Schema::hasTable('inventories_order_points')) {
            return 0;
        }

        return OrderPoint::query()
            ->with('product')
            ->get()
            ->filter(fn (OrderPoint $point): bool => ReplenishmentResource::isBelowMinimum($point))
            ->count();
    }

    protected function countOutOfStock(): int
    {
        if (! Schema::hasTable('inventories_order_points')) {
            return 0;
        }

        return OrderPoint::query()
            ->with('product')
            ->get()
            ->filter(function (OrderPoint $point): bool {
                $product = $point->product;

                return $product && (float) $product->available_qty <= 0;
            })
            ->count();
    }

    protected function replenishmentBelowMinimumUrl(): string
    {
        return FilamentUrl::appendLocaleToUrl(
            ReplenishmentResource::getUrl('index', FilamentUrl::withLocale([
                'activeTableView' => 'below_minimum',
            ])),
        );
    }
}
