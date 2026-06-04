<?php

namespace App\Filament\Inventory\Concerns;

use App\Support\FilamentUrl;
use App\Support\Inventory\InventoryStockCounter;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource;

trait InteractsWithInventoryStockCounts
{
    protected function countBelowMinimum(): int
    {
        return InventoryStockCounter::countBelowMinimum();
    }

    protected function countOutOfStock(): int
    {
        return InventoryStockCounter::countOutOfStock();
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
