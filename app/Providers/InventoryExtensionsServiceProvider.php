<?php

namespace App\Providers;

use App\Console\Commands\ConfigureOmrCurrency;
use App\Console\Commands\GenerateInventoryMovementReport;
use App\Console\Commands\NotifyLowStock;
use App\Console\Commands\RunReplenishment;
use App\Filament\Inventory\Pages\ManageReplenishment;
use App\Filament\Inventory\Pages\ViewInventoryProduct;
use App\Policies\OrderPointPolicy;
use App\Services\Inventory\ConsumptionTransferService;
use App\Services\Inventory\InventoryMovementReportService;
use App\Services\Inventory\ProductPurchaseHistoryService;
use App\Services\Inventory\ReplenishmentProcurementService;
use App\Support\SchemaMemo;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource\Pages\ManageReplenishment as BaseManageReplenishment;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages\ViewProduct as BaseViewProduct;
use Webkul\Inventory\Models\OrderPoint;

class InventoryExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReplenishmentProcurementService::class);
        $this->app->singleton(ConsumptionTransferService::class);
        $this->app->singleton(ProductPurchaseHistoryService::class);
        $this->app->singleton(InventoryMovementReportService::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('inventory-extensions'), 'inventory-extensions');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConfigureOmrCurrency::class,
                NotifyLowStock::class,
                GenerateInventoryMovementReport::class,
                RunReplenishment::class,
            ]);
        }

        if (! SchemaMemo::hasTable('inventories_order_points')) {
            return;
        }

        $this->app->booted(function (): void {
            $this->registerLivewireOverrides();
        });

        Filament::serving(function (): void {
            $this->registerLivewireOverrides();
        });

        $this->registerPolicies();
    }

    protected function registerLivewireOverrides(): void
    {
        Livewire::component(
            'webkul.inventory.filament.clusters.operations.resources.replenishment-resource.pages.manage-replenishment',
            ManageReplenishment::class,
        );

        Livewire::component(
            BaseManageReplenishment::class,
            ManageReplenishment::class,
        );

        Livewire::component(
            'webkul.inventory.filament.clusters.products.resources.product-resource.pages.view-product',
            ViewInventoryProduct::class,
        );

        Livewire::component(
            BaseViewProduct::class,
            ViewInventoryProduct::class,
        );
    }

    protected function registerPolicies(): void
    {
        Gate::policy(OrderPoint::class, OrderPointPolicy::class);
    }
}
