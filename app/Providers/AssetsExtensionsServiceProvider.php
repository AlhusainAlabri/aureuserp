<?php

namespace App\Providers;

use App\Console\Commands\NotifyDueSoonAssetBorrowings;
use App\Filament\Extensions\AssetResourceExtensions\ListAssets;
use App\Services\Assets\AssetBorrowingEventService;
use App\Services\Assets\AssetBorrowingNotificationService;
use App\Services\Assets\AssetSignatureStorageService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssetsExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AssetBorrowingEventService::class);
        $this->app->singleton(AssetBorrowingNotificationService::class);
        $this->app->singleton(AssetSignatureStorageService::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('assets-extensions'), 'assets-extensions');

        if ($this->app->runningInConsole()) {
            $this->commands([
                NotifyDueSoonAssetBorrowings::class,
            ]);
        }

        if (! Schema::hasTable('assets')) {
            return;
        }

        $this->registerPermissions();

        $this->app->booted(function (): void {
            $this->registerLivewireLocaleOverrides();
        });

        Filament::serving(function (): void {
            $this->registerLivewireLocaleOverrides();
        });
    }

    protected function registerLivewireLocaleOverrides(): void
    {
        Livewire::component(
            'webkul.assets.filament.resources.asset-resource.pages.list-assets',
            ListAssets::class,
        );

        Livewire::component(
            \Webkul\Assets\Filament\Resources\AssetResource\Pages\ListAssets::class,
            ListAssets::class,
        );
    }

    protected function registerPermissions(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'page_assets_dashboard',
            'page_my_borrowing_requests',
            'page_pending_borrowing_requests',
            'page_my_borrowed_assets',
            'request_borrow_assets_asset',
            'approve_borrowing_assets_asset',
            'reject_borrowing_assets_asset',
            'view_assets_asset_borrowing',
            'view_any_assets_asset_borrowing',
            'widget_assets_summary_widget',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $managerRoles = ['Admin', 'admin_manager', 'super_admin', 'general_manager', 'hr_manager', 'manager'];
        $employeePermissions = [
            'page_my_borrowing_requests',
            'page_my_borrowed_assets',
            'request_borrow_assets_asset',
        ];

        foreach ($managerRoles as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($permissions as $permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        foreach (['employee'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($employeePermissions as $permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
