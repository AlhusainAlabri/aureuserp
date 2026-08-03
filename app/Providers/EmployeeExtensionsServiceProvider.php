<?php

namespace App\Providers;

use App\Filament\Employees\Resources\EmployeeResource\Pages\ListEmployees as ExtendedListEmployees;
use App\Filament\Employees\Resources\SubmissionResource\Pages\ListSubmissions as ExtendedListSubmissions;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ReflectionClass;
use Webkul\Employee\Filament\Clusters\Configurations as EmployeeConfigurations;
use Webkul\Employee\Filament\Clusters\Reportings;
use Webkul\Employee\Filament\Resources\CoreSubmissionResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ListEmployees as BaseListEmployees;
use Webkul\Employee\Filament\Resources\SubmissionResource;
use Webkul\Employee\Filament\Resources\SubmissionResource\Pages\ListSubmissions as BaseListSubmissions;

class EmployeeExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->restoreGlobalPageNavigationRegistration();

        $this->registerReportingsClusterOverride();

        $this->registerConfigurationsClusterOverride();

        $this->registerSubmissionResourceOverride();
    }

    public function boot(): void
    {
        $this->restoreGlobalPageNavigationRegistration();

        $this->configureEmployeeNavigationSort();

        $this->app->booted(function (): void {
            $this->registerLivewireOverrides();
        });

        Filament::serving(function (): void {
            $this->registerLivewireOverrides();
        });
    }

    protected function restoreGlobalPageNavigationRegistration(): void
    {
        $reflection = new ReflectionClass(Page::class);
        $property = $reflection->getProperty('shouldRegisterNavigation');
        $property->setAccessible(true);
        $property->setValue(null, true);
    }

    protected function configureEmployeeNavigationSort(): void
    {
        if (! class_exists(SubmissionResource::class)) {
            return;
        }

        SubmissionResource::navigationSort(10);
    }

    protected function registerReportingsClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== Reportings::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Employee/Filament/Clusters/Reportings.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerConfigurationsClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== EmployeeConfigurations::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Employee/Filament/Clusters/Configurations.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerSubmissionResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== SubmissionResource::class) {
                    return false;
                }

                $this->ensureCoreSubmissionResourceIsLoaded();

                require app_path('Overrides/Webkul/Employee/Filament/Resources/SubmissionResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreSubmissionResourceIsLoaded(): void
    {
        if (class_exists(CoreSubmissionResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/employees/src/Filament/Resources/SubmissionResource.php');
        $cachePath = storage_path('framework/cache/core_submission_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass SubmissionResource\b/', 'class CoreSubmissionResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function registerLivewireOverrides(): void
    {
        if (! class_exists(BaseListEmployees::class)) {
            return;
        }

        Livewire::component(
            'webkul.employee.filament.resources.employee-resource.pages.list-employees',
            ExtendedListEmployees::class,
        );

        Livewire::component(
            BaseListEmployees::class,
            ExtendedListEmployees::class,
        );

        if (! class_exists(BaseListSubmissions::class)) {
            return;
        }

        Livewire::component(
            'webkul.employee.filament.resources.submission-resource.pages.list-submissions',
            ExtendedListSubmissions::class,
        );

        Livewire::component(
            BaseListSubmissions::class,
            ExtendedListSubmissions::class,
        );
    }
}
