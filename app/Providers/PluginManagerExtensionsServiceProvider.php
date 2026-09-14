<?php

namespace App\Providers;

use App\Http\Middleware\DenyPluginsUiAccess;
use App\Support\PluginManager\PluginManagerUi;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PluginManagerExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (?object $user, string $ability): ?bool {
            if (! PluginManagerUi::isHidden()) {
                return null;
            }

            if (str_contains($ability, 'plugin_manager::plugin')) {
                return false;
            }

            return null;
        });

        Filament::serving(function (): void {
            if (! PluginManagerUi::isHidden()) {
                return;
            }

            $panel = Filament::getCurrentPanel();

            if ($panel?->getId() !== 'admin') {
                return;
            }

            $panel->authMiddleware([
                DenyPluginsUiAccess::class,
            ], isPersistent: true);
        });
    }
}
