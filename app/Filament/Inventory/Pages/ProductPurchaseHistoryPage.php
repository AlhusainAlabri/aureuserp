<?php

namespace App\Filament\Inventory\Pages;

use App\Filament\Inventory\Concerns\InteractsWithProductPurchaseHistoryTab;
use App\Services\Inventory\ProductPurchaseHistoryService;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Product\Models\Product;

class ProductPurchaseHistoryPage extends Page
{
    use HasPageShield;
    use InteractsWithProductPurchaseHistoryTab;

    protected string $view = 'filament.inventory.pages.product-purchase-history';

    protected static ?string $slug = 'inventory/products/products/{record}/purchase-history';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static bool $shouldRegisterNavigation = false;

    public ?Product $productRecord = null;

    protected static function getPagePermission(): ?string
    {
        return 'page_inventory_product_purchase_history';
    }

    public function mount(int $record): void
    {
        $this->productRecord = Product::query()->findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->productRecord === null) {
            return __('inventory-extensions::purchase_history.title');
        }

        return __('inventory-extensions::purchase_history.title').': '.$this->productRecord->name;
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Schema::hasTable('products_products')
            && parent::canAccess($parameters);
    }

    protected function getHeaderWidgets(): array
    {
        return $this->getRecordNavigationTabsWidget();
    }

    #[Computed]
    public function historyRows(): Collection
    {
        if ($this->productRecord === null) {
            return collect();
        }

        return app(ProductPurchaseHistoryService::class)
            ->historyForProduct($this->productRecord);
    }

    protected function getHeaderActions(): array
    {
        if ($this->productRecord === null) {
            return [];
        }

        return [
            Action::make('back')
                ->label(__('inventory-extensions::purchase_history.back_to_product'))
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(
                    ProductResource::getUrl('view', ['record' => $this->productRecord]),
                ))
                ->color('gray'),
        ];
    }
}
