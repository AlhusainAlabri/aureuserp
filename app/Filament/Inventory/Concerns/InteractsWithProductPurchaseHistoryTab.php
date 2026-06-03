<?php

namespace App\Filament\Inventory\Concerns;

use App\Filament\Inventory\Pages\ProductPurchaseHistoryPage;
use App\Support\FilamentUrl;
use Filament\Resources\Pages\Page;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Product\Models\Product;
use Webkul\Support\Filament\Widgets\RecordNavigationTabs;

trait InteractsWithProductPurchaseHistoryTab
{
    protected function getRecordNavigationTabsWidget(): array
    {
        $record = $this->getProductRecordForPurchaseHistory();

        if ($record === null) {
            return [];
        }

        if ($this instanceof Page) {
            $navigationItems = collect(
                $this->convertNavigationItemsToArray(
                    ProductResource::getRecordSubNavigation($this),
                ),
            );
        } else {
            $navigationItems = collect($this->buildFallbackProductNavigationItems($record));
        }

        if ($this->shouldShowPurchaseHistoryTab()) {
            $navigationItems->push([
                'label'      => __('inventory-extensions::navigation.purchase_history'),
                'url'        => FilamentUrl::appendLocaleToUrl(
                    ProductPurchaseHistoryPage::getUrl(['record' => $record->getKey()]),
                ),
                'isActive'   => $this instanceof ProductPurchaseHistoryPage,
                'isHidden'   => false,
                'icon'       => 'heroicon-o-banknotes',
                'activeIcon' => 'heroicon-s-banknotes',
                'badge'      => null,
                'badgeColor' => null,
            ]);
        }

        return [
            RecordNavigationTabs::make([
                'navigationItems' => $navigationItems->values()->all(),
            ]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildFallbackProductNavigationItems(Product $record): array
    {
        $items = [
            [
                'label'      => __('filament-panels::resources/pages/view-record.title', ['label' => ProductResource::getModelLabel()]),
                'url'        => FilamentUrl::appendLocaleToUrl(ProductResource::getUrl('view', ['record' => $record])),
                'isActive'   => false,
                'isHidden'   => false,
                'icon'       => 'heroicon-o-eye',
                'activeIcon' => 'heroicon-s-eye',
                'badge'      => null,
                'badgeColor' => null,
            ],
        ];

        if (ProductResource::hasPage('quantities')) {
            $items[] = [
                'label'      => __('inventories::filament/clusters/products/resources/product/pages/manage-quantities.title'),
                'url'        => FilamentUrl::appendLocaleToUrl(ProductResource::getUrl('quantities', ['record' => $record])),
                'isActive'   => false,
                'isHidden'   => false,
                'icon'       => 'heroicon-o-scale',
                'activeIcon' => 'heroicon-s-scale',
                'badge'      => null,
                'badgeColor' => null,
            ];
        }

        if (ProductResource::hasPage('moves')) {
            $items[] = [
                'label'      => __('inventories::filament/clusters/products/resources/product/pages/manage-moves.title'),
                'url'        => FilamentUrl::appendLocaleToUrl(ProductResource::getUrl('moves', ['record' => $record])),
                'isActive'   => false,
                'isHidden'   => false,
                'icon'       => 'heroicon-o-arrows-right-left',
                'activeIcon' => 'heroicon-s-arrows-right-left',
                'badge'      => null,
                'badgeColor' => null,
            ];
        }

        return $items;
    }

    protected function shouldShowPurchaseHistoryTab(): bool
    {
        $record = $this->getProductRecordForPurchaseHistory();

        if (! $record instanceof Product || ! $record->is_storable) {
            return false;
        }

        return auth()->user()?->can('page_inventory_product_purchase_history') ?? false;
    }

    protected function getProductRecordForPurchaseHistory(): ?Product
    {
        if ($this instanceof ProductPurchaseHistoryPage) {
            return $this->productRecord;
        }

        if (method_exists($this, 'getRecord')) {
            $record = $this->getRecord();

            return $record instanceof Product ? $record : null;
        }

        return null;
    }
}
