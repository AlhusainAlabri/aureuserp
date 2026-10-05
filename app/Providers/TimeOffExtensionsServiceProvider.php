<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\TimeOff\Filament\Clusters\Configurations;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\ActivityTypeResource;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\CoreLeaveTypeResource;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\LeaveTypeResource;

class TimeOffExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfigurationsClusterOverride();
        $this->registerActivityTypeResourceOverride();
        $this->registerLeaveTypeResourceOverride();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('time-off-extensions'), 'time-off-extensions');
    }

    protected function registerConfigurationsClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== Configurations::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/TimeOff/Filament/Clusters/Configurations.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerActivityTypeResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== ActivityTypeResource::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/TimeOff/Filament/Clusters/Configurations/Resources/ActivityTypeResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerLeaveTypeResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class === CoreLeaveTypeResource::class) {
                    $this->ensureCoreLeaveTypeResourceIsLoaded();

                    return true;
                }

                if ($class !== LeaveTypeResource::class) {
                    return false;
                }

                $this->ensureCoreLeaveTypeResourceIsLoaded();

                require app_path('Overrides/Webkul/TimeOff/Filament/Clusters/Configurations/Resources/LeaveTypeResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreLeaveTypeResourceIsLoaded(): void
    {
        if (class_exists(CoreLeaveTypeResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/time-off/src/Filament/Clusters/Configurations/Resources/LeaveTypeResource.php');
        $cachePath = storage_path('framework/cache/core_leave_type_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass LeaveTypeResource\b/', 'class CoreLeaveTypeResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }
}
