<?php

namespace App\Providers;

use App\Models\Invoices\InvoiceAttachment;
use Illuminate\Support\ServiceProvider;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\PaymentTerm;
use Webkul\Invoice\Filament\Clusters\Customers as CustomersCluster;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\CoreInvoiceResource;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource;
use Webkul\Invoice\Filament\Clusters\Vendors as VendorsCluster;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\CoreBillResource;
use Webkul\Invoice\Models\Bill;
use Webkul\Invoice\Models\Invoice;

class AccountExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerInvoiceResourceOverride();
        $this->registerBillResourceOverride();
        $this->registerCustomersClusterOverride();
        $this->registerVendorsClusterOverride();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('payment-terms'), 'payment-terms');
        $this->loadTranslationsFrom(lang_path('invoices-extensions'), 'invoices-extensions');

        if (! class_exists(PaymentTerm::class)) {
            return;
        }

        PaymentTerm::retrieved(function (PaymentTerm $term): void {
            if (app()->getLocale() !== 'ar') {
                return;
            }

            $translated = __('payment-terms::names.'.$term->id);

            if (! str_starts_with($translated, 'payment-terms::')) {
                $term->setAttribute('name', $translated);
            }
        });

        $this->registerInvoiceDocumentRelation();
    }

    protected function registerInvoiceDocumentRelation(): void
    {
        if (! class_exists(Move::class)) {
            return;
        }

        $relation = fn (Move $move) => $move->hasMany(InvoiceAttachment::class, 'invoice_id');

        Move::resolveRelationUsing('documents', $relation);

        if (class_exists(Invoice::class)) {
            Invoice::resolveRelationUsing('documents', $relation);
        }

        if (class_exists(Bill::class)) {
            Bill::resolveRelationUsing('documents', $relation);
        }
    }

    protected function registerInvoiceResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== InvoiceResource::class) {
                    return false;
                }

                $this->ensureCoreInvoiceResourceIsLoaded();

                require app_path('Overrides/Webkul/Invoice/Filament/Clusters/Customers/Resources/InvoiceResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerBillResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== BillResource::class) {
                    return false;
                }

                $this->ensureCoreBillResourceIsLoaded();

                require app_path('Overrides/Webkul/Invoice/Filament/Clusters/Vendors/Resources/BillResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreInvoiceResourceIsLoaded(): void
    {
        if (class_exists(CoreInvoiceResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/invoices/src/Filament/Clusters/Customers/Resources/InvoiceResource.php');
        $cachePath = storage_path('framework/cache/core_invoice_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass InvoiceResource\b/', 'class CoreInvoiceResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function ensureCoreBillResourceIsLoaded(): void
    {
        if (class_exists(CoreBillResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/invoices/src/Filament/Clusters/Vendors/Resources/BillResource.php');
        $cachePath = storage_path('framework/cache/core_bill_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass BillResource\b/', 'class CoreBillResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function registerCustomersClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== CustomersCluster::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Invoice/Filament/Clusters/Customers.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerVendorsClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== VendorsCluster::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Invoice/Filament/Clusters/Vendors.php');

                return true;
            },
            prepend: true,
        );
    }
}
