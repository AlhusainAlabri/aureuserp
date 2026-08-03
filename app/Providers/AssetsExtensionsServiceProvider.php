<?php

namespace App\Providers;

use App\Console\Commands\NotifyDueSoonAssetBorrowings;
use App\Filament\Extensions\AssetResourceExtensions\ListAssets;
use App\Services\Assets\AssetBorrowingEventService;
use App\Services\Assets\AssetBorrowingNotificationService;
use App\Services\Assets\AssetSignatureStorageService;
use App\Support\SchemaMemo;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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

        if (! SchemaMemo::hasTable('assets')) {
            return;
        }

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
}
