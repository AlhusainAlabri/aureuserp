<?php

namespace App\Providers;

use App\Models\Invoices\InvoiceAttachment;
use App\Services\Accounts\ExtendedAccountManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions\ConfirmAction;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions\PreviewAction;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions\PrintAndSendAction;
use Webkul\Account\Livewire\InvoiceSummary;
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
        $this->registerPrintAndSendActionOverride();
        $this->registerPreviewActionOverride();
        $this->registerConfirmActionOverride();
        $this->registerInvoiceSummaryOverride();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('payment-terms'), 'payment-terms');
        $this->loadTranslationsFrom(lang_path('invoices-extensions'), 'invoices-extensions');

        View::prependNamespace('accounts', resource_path('views/vendor/accounts'));

        $this->app->singleton('account', ExtendedAccountManager::class);

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

    protected function registerPrintAndSendActionOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== PrintAndSendAction::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Account/Filament/Resources/InvoiceResource/Actions/PrintAndSendAction.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerPreviewActionOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== PreviewAction::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Account/Filament/Resources/InvoiceResource/Actions/PreviewAction.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerConfirmActionOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== ConfirmAction::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Account/Filament/Resources/InvoiceResource/Actions/ConfirmAction.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerInvoiceSummaryOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== InvoiceSummary::class) {
                    return false;
                }

                $this->ensureCoreInvoiceSummaryIsLoaded();

                require app_path('Overrides/Webkul/Account/Livewire/InvoiceSummary.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreInvoiceSummaryIsLoaded(): void
    {
        if (class_exists('Webkul\Account\Livewire\CoreInvoiceSummary', false)) {
            return;
        }

        $source = base_path('plugins/webkul/accounts/src/Livewire/InvoiceSummary.php');
        $cachePath = storage_path('framework/cache/core_invoice_summary.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass InvoiceSummary\b/', 'class CoreInvoiceSummary', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
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
