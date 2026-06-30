<?php

namespace App\Providers;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Filament\Purchases\Pages\EditPurchaseOrder as ExtendedEditPurchaseOrder;
use App\Filament\Purchases\Pages\ViewPurchaseOrder as AppViewPurchaseOrder;
use App\Listeners\Purchases\ConvertApprovedPurchaseToExpense;
use App\Models\Purchases\PurchaseOrderAttachment;
use App\Services\Purchases\InternalRequestLineService;
use App\Services\Purchases\PurchaseExpenseConversionService;
use App\Services\Purchases\PurchasePaymentService;
use App\Support\PermissionTables;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Filament\Admin\Clusters\Orders;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\CorePurchaseOrderResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\CoreQuotationResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\EditOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Models\PurchaseOrder;
use Wezlo\FilamentApproval\Events\ApprovalCompleted;

class PurchaseExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PurchasePaymentService::class);
        $this->app->singleton(PurchaseExpenseConversionService::class);
        $this->app->singleton(InternalRequestLineService::class);

        $this->registerPurchaseOrderResourceOverride();
        $this->registerQuotationResourceOverride();
        $this->registerOrdersClusterOverride();
        $this->registerPurchasePageOverrides();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('purchases-extensions'), 'purchases-extensions');
        $this->loadTranslationsFrom(lang_path('purchases'), 'purchases');

        if (! class_exists(Order::class)) {
            return;
        }

        $this->registerLivewireOverrides();
        $this->registerPermissions();
        $this->registerListeners();
        $this->registerOrderHooks();
        $this->registerOrderDocumentRelation();
    }

    protected function registerOrderDocumentRelation(): void
    {
        Order::resolveRelationUsing(
            'documents',
            fn (Order $order) => $order->hasMany(PurchaseOrderAttachment::class, 'purchase_order_id'),
        );
    }

    protected function registerPurchaseOrderResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== PurchaseOrderResource::class) {
                    return false;
                }

                $this->ensureCorePurchaseOrderResourceIsLoaded();

                require app_path('Overrides/Webkul/Purchase/Filament/Admin/Clusters/Orders/Resources/PurchaseOrderResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCorePurchaseOrderResourceIsLoaded(): void
    {
        if (class_exists(CorePurchaseOrderResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/PurchaseOrderResource.php');
        $cachePath = storage_path('framework/cache/core_purchase_order_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass PurchaseOrderResource\b/', 'class CorePurchaseOrderResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function registerQuotationResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== QuotationResource::class) {
                    return false;
                }

                $this->ensureCoreQuotationResourceIsLoaded();

                require app_path('Overrides/Webkul/Purchase/Filament/Admin/Clusters/Orders/Resources/QuotationResource.php');

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

        $source = base_path('plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/QuotationResource.php');
        $cachePath = storage_path('framework/cache/core_purchase_quotation_resource.php');

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

    protected function registerOrdersClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== Orders::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Purchase/Filament/Admin/Clusters/Orders.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerPurchasePageOverrides(): void
    {
        $pages = [
            ViewOrder::class => [
                'source'     => 'plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/OrderResource/Pages/ViewOrder.php',
                'cache'      => 'core_purchase_view_order.php',
                'class'      => 'ViewOrder',
                'core_class' => 'CoreViewOrder',
                'override'   => 'Overrides/Webkul/Purchase/Filament/Admin/Clusters/Orders/Resources/OrderResource/Pages/ViewOrder.php',
            ],
            EditOrder::class => [
                'source'     => 'plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/OrderResource/Pages/EditOrder.php',
                'cache'      => 'core_purchase_edit_order.php',
                'class'      => 'EditOrder',
                'core_class' => 'CoreEditOrder',
                'override'   => 'Overrides/Webkul/Purchase/Filament/Admin/Clusters/Orders/Resources/OrderResource/Pages/EditOrder.php',
            ],
            ViewQuotation::class => [
                'override' => 'Overrides/Webkul/Purchase/Filament/Admin/Clusters/Orders/Resources/QuotationResource/Pages/ViewQuotation.php',
            ],
            EditQuotation::class => [
                'override' => 'Overrides/Webkul/Purchase/Filament/Admin/Clusters/Orders/Resources/QuotationResource/Pages/EditQuotation.php',
            ],
        ];

        spl_autoload_register(
            function (string $class) use ($pages): bool {
                if (! isset($pages[$class])) {
                    return false;
                }

                $config = $pages[$class];

                if (isset($config['source'], $config['cache'], $config['class'], $config['core_class'])) {
                    $this->ensureCorePurchasePageIsLoaded(
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

    protected function ensureCorePurchasePageIsLoaded(
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

    protected function registerOrderHooks(): void
    {
        Order::creating(function (Order $order): void {
            if (empty($order->currency_id)) {
                $order->currency_id = PurchaseOrderResourceExtensions::defaultOmrCurrencyId();
            }
        });

        Order::saved(function (Order $order): void {
            if (! Schema::hasColumn('purchases_orders', 'amount_remaining')) {
                return;
            }

            if ($order->wasChanged('total_amount') || $order->wasRecentlyCreated) {
                app(PurchasePaymentService::class)->syncAmounts($order);
            }
        });

        Order::updated(function (Order $order): void {
            if (! $order instanceof PurchaseOrder) {
                return;
            }

            if (! $order->wasChanged('state')) {
                return;
            }

            if (! in_array($order->state, [OrderState::PURCHASE, OrderState::DONE], true)) {
                return;
            }

            app(PurchaseExpenseConversionService::class)->convertIfEligible($order);
        });
    }

    protected function registerLivewireOverrides(): void
    {
        Livewire::component(
            'webkul.purchase.filament.admin.clusters.orders.resources.purchase-order-resource.pages.view-purchase-order',
            AppViewPurchaseOrder::class
        );

        Livewire::component(
            'webkul.purchase.filament.admin.clusters.orders.resources.purchase-order-resource.pages.edit-purchase-order',
            ExtendedEditPurchaseOrder::class
        );
    }

    protected function registerListeners(): void
    {
        Event::listen(ApprovalCompleted::class, ConvertApprovedPurchaseToExpense::class);
    }

    protected function registerPermissions(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady()) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'page_purchases_department_report',
            'page_MyRequests',
            'page_InternalRequests',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web'],
            );
        }

        foreach (['Admin', 'admin_manager', 'super_admin', 'general_manager', 'finance_manager', 'manager'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }
}
