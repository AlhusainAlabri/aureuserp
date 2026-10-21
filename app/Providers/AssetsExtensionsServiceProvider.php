<?php

namespace App\Providers;

use App\Console\Commands\NotifyDueSoonAssetBorrowings;
use App\Services\Assets\AssetBorrowingEventService;
use App\Services\Assets\AssetBorrowingNotificationService;
use App\Services\Assets\AssetSignatureStorageService;
use App\Support\SchemaMemo;
use Illuminate\Support\ServiceProvider;

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
    }
}
