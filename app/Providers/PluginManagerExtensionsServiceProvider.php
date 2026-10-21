<?php

namespace App\Providers;

use App\Policies\PluginPolicy;
use App\Support\PluginManager\PluginManagerUi;
use App\Support\PluginManager\PluginsNavigationManager;
use Filament\Navigation\NavigationManager;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Webkul\PluginManager\Models\Plugin;

class PluginManagerExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(NavigationManager::class, function (): NavigationManager {
            return new PluginsNavigationManager;
        });
    }

    public function boot(): void
    {
        Gate::policy(Plugin::class, PluginPolicy::class);

        $this->app->booted(function (): void {
            $this->prependPluginsUiGateBeforeCallback();
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

        array_unshift($callbacks, function (?object $user, string $ability, mixed ...$arguments): ?bool {
            if (! PluginManagerUi::blocksPluginPermission()) {
                return null;
            }

            if (str_contains($ability, 'plugin_manager_plugin') || self::argumentIsPlugin($arguments)) {
                return false;
            }

            return null;
        });

        $property->setValue($gate, $callbacks);
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    protected static function argumentIsPlugin(array $arguments): bool
    {
        $target = $arguments[0] ?? null;

        if (is_object($target)) {
            return $target instanceof Plugin;
        }

        return $target === Plugin::class;
    }
}
