<?php

namespace App\Providers;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Filament\Purchases\Pages\EditPurchaseOrder;
use App\Filament\Purchases\Pages\ViewPurchaseOrder as AppViewPurchaseOrder;
use App\Listeners\Purchases\ConvertApprovedPurchaseToExpense;
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
    }

    protected function registerOrderHooks(): void
    {
        Order::creating(function (Order $order): void {
            if (! Schema::hasColumn('purchases_orders', 'request_type')) {
                return;
            }

            if (
                PurchaseOrderResourceExtensions::isInternalRequest($order->request_type)
                && empty($order->currency_id)
            ) {
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
            EditPurchaseOrder::class
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

        $permission = Permission::firstOrCreate(
            ['name' => 'page_purchases_department_report'],
            ['guard_name' => 'web']
        );

        foreach (['Admin', 'admin_manager', 'super_admin', 'general_manager', 'finance_manager', 'manager'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
