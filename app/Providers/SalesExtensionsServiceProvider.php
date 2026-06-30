<?php

namespace App\Providers;

use App\Filament\Sales\Pages\ListOrders as ExtendedListOrders;
use App\Filament\Sales\Pages\ListQuotations as ExtendedListQuotations;
use App\Models\Sales\SalesOrderAttachment;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Webkul\Sale\Filament\Clusters\Orders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CoreQuotationResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\EditOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\ListOrders as BaseListOrders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ListQuotations as BaseListQuotations;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ManageInvoices;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation;
use Webkul\Sale\Models\Order;

class SalesExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerQuotationResourceOverride();
        $this->registerOrderResourceOverride();
        $this->registerOrdersClusterOverride();
        $this->registerManageInvoicesOverride();
        $this->registerSalesPageOverrides();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('sales-extensions'), 'sales-extensions');

        $this->registerOrderDocumentRelation();

        $this->app->booted(function (): void {
            $this->registerLivewireOverrides();
        });

        Filament::serving(function (): void {
            $this->registerLivewireOverrides();
        });
    }

    protected function registerOrderDocumentRelation(): void
    {
        if (! class_exists(Order::class)) {
            return;
        }

        Order::resolveRelationUsing(
            'documents',
            fn (Order $order) => $order->hasMany(SalesOrderAttachment::class, 'sales_order_id'),
        );
    }

    protected function registerSalesPageOverrides(): void
    {
        $pages = [
            ViewQuotation::class => [
                'source'         => 'plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Pages/ViewQuotation.php',
                'cache'          => 'core_view_quotation.php',
                'class'          => 'ViewQuotation',
                'core_class'     => 'CoreViewQuotation',
                'override'       => 'Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/QuotationResource/Pages/ViewQuotation.php',
            ],
            EditQuotation::class => [
                'source'         => 'plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Pages/EditQuotation.php',
                'cache'          => 'core_edit_quotation.php',
                'class'          => 'EditQuotation',
                'core_class'     => 'CoreEditQuotation',
                'override'       => 'Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/QuotationResource/Pages/EditQuotation.php',
            ],
            ViewOrder::class => [
                'override' => 'Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/OrderResource/Pages/ViewOrder.php',
            ],
            EditOrder::class => [
                'override' => 'Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/OrderResource/Pages/EditOrder.php',
            ],
        ];

        spl_autoload_register(
            function (string $class) use ($pages): bool {
                if (! isset($pages[$class])) {
                    return false;
                }

                $config = $pages[$class];

                if (isset($config['source'], $config['cache'], $config['class'], $config['core_class'])) {
                    $this->ensureCorePageIsLoaded(
                        $config['source'],
                        $config['cache'],
                        $config['class'],
                        $config['core_class'],
                    );
                }

                require app_path($config['override']);

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCorePageIsLoaded(
        string $sourceRelativePath,
        string $cacheFile,
        string $className,
        string $coreClassName,
    ): void {
        $source = base_path($sourceRelativePath);
        $namespace = $this->resolveClassNamespaceFromFile($source);
        $coreClass = $namespace.'\\'.$coreClassName;

        if (class_exists($coreClass, false)) {
            return;
        }

        $cachePath = storage_path('framework/cache/'.$cacheFile);

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass '.$className.'\b/', 'class '.$coreClassName, $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function resolveClassNamespaceFromFile(string $source): string
    {
        $code = file_get_contents($source);

        if (preg_match('/namespace\s+([^;]+);/', $code, $matches) !== 1) {
            throw new \RuntimeException("Unable to resolve namespace for {$source}");
        }

        return $matches[1];
    }

    protected function registerQuotationResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== QuotationResource::class) {
                    return false;
                }

                $this->ensureCoreQuotationResourceIsLoaded();

                require app_path('Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/QuotationResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreQuotationResourceIsLoaded(): void
    {
        if (class_exists(CoreQuotationResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource.php');
        $cachePath = storage_path('framework/cache/core_quotation_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass QuotationResource\b/', 'class CoreQuotationResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
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

    protected function registerOrdersClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== Orders::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Sale/Filament/Clusters/Orders.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerManageInvoicesOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== ManageInvoices::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/QuotationResource/Pages/ManageInvoices.php');

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
