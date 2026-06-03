<?php

namespace App\Providers;

use App\Filament\Sales\Pages\ListOrders as ExtendedListOrders;
use App\Filament\Sales\Pages\ListQuotations as ExtendedListQuotations;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\ListOrders as BaseListOrders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ListQuotations as BaseListQuotations;

class SalesExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerOrderResourceOverride();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('sales-extensions'), 'sales-extensions');

        $this->app->booted(function (): void {
            $this->registerLivewireOverrides();
        });

        Filament::serving(function (): void {
            $this->registerLivewireOverrides();
        });
    }

    protected function registerOrderResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== OrderResource::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/OrderResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerLivewireOverrides(): void
    {
        if (! class_exists(BaseListQuotations::class)) {
            return;
        }

        Livewire::component(BaseListQuotations::class, ExtendedListQuotations::class);
        Livewire::component(BaseListOrders::class, ExtendedListOrders::class);
    }
}
