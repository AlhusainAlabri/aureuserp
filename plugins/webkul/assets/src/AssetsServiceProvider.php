<?php

namespace Webkul\Assets;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Webkul\Assets\Console\NotifyOverdueAssetBorrowings;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Assets\Policies\AssetBorrowingPolicy;
use Webkul\Assets\Policies\AssetPolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class AssetsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'assets';

    public static string $viewNamespace = 'assets';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasMigrations([
                '2026_05_30_100000_create_assets_table',
                '2026_05_30_100001_create_asset_borrowings_table',
                '2026_05_31_100000_add_request_fields_to_asset_borrowings_table',
                '2026_05_31_100001_add_signature_fields_to_asset_borrowings_table',
                '2026_05_31_100002_add_vehicle_fields_to_assets_table',
            ])
            ->hasDependencies([
                'employees',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations()
                    ->endWith(function (InstallCommand $command): void {
                        $command->call('shield:generate', [
                            '--resource' => 'AssetResource',
                            '--panel'    => 'admin',
                        ]);

                        $ar = require __DIR__.'/../resources/lang/ar/assets.php';
                        $en = require __DIR__.'/../resources/lang/en/assets.php';

                        $command->info($ar['install']['success'].' '.$en['install']['success']);
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('assets');
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(AssetsPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(AssetBorrowing::class, AssetBorrowingPolicy::class);

        $this->commands([
            NotifyOverdueAssetBorrowings::class,
        ]);
    }
}
