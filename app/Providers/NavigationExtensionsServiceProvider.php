<?php

namespace App\Providers;

use App\Filament\Clusters\Settings\Pages\ManageNavigation;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class NavigationExtensionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Filament::serving(function (): void {
            FilamentView::registerRenderHook(
                PanelsRenderHook::SCRIPTS_BEFORE,
                fn (): string => view('filament.components.navigation.sidebar-init-storage')->render(),
            );

            FilamentView::registerRenderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => view('filament.components.navigation.sidebar-init')->render(),
            );
        });
    }

    public function register(): void
    {
        //
    }

    /**
     * @return array<class-string>
     */
    public static function settingsPages(): array
    {
        return [
            ManageNavigation::class,
        ];
    }
}
