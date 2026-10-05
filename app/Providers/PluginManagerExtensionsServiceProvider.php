<?php

namespace App\Providers;

use App\Http\Middleware\DenyPluginsUiAccess;
use App\Policies\PluginPolicy;
use App\Support\PluginManager\PluginManagerUi;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Webkul\PluginManager\Models\Plugin;

class PluginManagerExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Plugin::class, PluginPolicy::class);

        $this->app->booted(function (): void {
            $this->prependPluginsUiGateBeforeCallback();
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

    protected function prependPluginsUiGateBeforeCallback(): void
    {
        $gate = Gate::getFacadeRoot();

        if (! $gate instanceof GateContract) {
            return;
        }

        $reflection = new ReflectionClass($gate);
        $property = $reflection->getProperty('beforeCallbacks');
        $property->setAccessible(true);

        /** @var array<int, callable> $callbacks */
        $callbacks = $property->getValue($gate);

        array_unshift($callbacks, function (?object $user, string $ability): ?bool {
            if (! PluginManagerUi::isHidden()) {
                return null;
            }

            if (str_contains($ability, 'plugin_manager_plugin')) {
                return false;
            }

            return null;
        });

        $property->setValue($gate, $callbacks);
    }
}
