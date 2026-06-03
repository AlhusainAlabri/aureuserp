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
