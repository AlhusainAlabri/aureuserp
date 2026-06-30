<?php

namespace App\Providers;

use App\Filament\Contacts\Resources\PartnerResource\Pages\ListPartners as ExtendedListPartners;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ReflectionClass;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ListPartners as BaseListPartners;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ManageAddresses;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ManageContacts;

class ContactExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->restoreGlobalPageNavigationRegistration();

        $this->registerConfigurationsClusterOverride();
        $this->registerConfigurationPageOverrides();
        $this->registerConfigurationResourceOverrides();
        $this->registerPartnerResourceOverride();
        $this->ensurePartnerResourceOverrideIsLoaded();
    }

    public function boot(): void
    {
        $this->restoreGlobalPageNavigationRegistration();
        $this->loadTranslationsFrom(lang_path('contacts-extensions'), 'contacts-extensions');

        $this->app->booted(function (): void {
            $this->registerLivewireOverrides();
        });

        Filament::serving(function (): void {
            $this->registerLivewireOverrides();
        });
    }

    /**
     * A previous reflection-based approach set this flag on the parent Page class,
     * which hid every Filament page from navigation. Always restore it.
     */
    protected function restoreGlobalPageNavigationRegistration(): void
    {
        $reflection = new ReflectionClass(Page::class);
        $property = $reflection->getProperty('shouldRegisterNavigation');
        $property->setAccessible(true);
        $property->setValue(null, true);
    }

    protected function registerConfigurationsClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== Configurations::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Contact/Filament/Clusters/Configurations.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerConfigurationResourceOverrides(): void
    {
        $resources = [
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\TagResource'         => 'TagResource.php',
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\TitleResource'       => 'TitleResource.php',
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\IndustryResource'    => 'IndustryResource.php',
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\BankResource'        => 'BankResource.php',
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\BankAccountResource' => 'BankAccountResource.php',
        ];

        $basePath = app_path('Overrides/Webkul/Contact/Filament/Clusters/Configurations/Resources/');

        spl_autoload_register(
            function (string $class) use ($resources, $basePath): bool {
                if (! isset($resources[$class])) {
                    return false;
                }

                require $basePath.$resources[$class];

                return true;
            },
            prepend: true,
        );
    }

    protected function registerConfigurationPageOverrides(): void
    {
        $pages = [
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\TagResource\\Pages\\ManageTags'                 => 'TagResource/Pages/ManageTags.php',
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\TitleResource\\Pages\\ManageTitles'             => 'TitleResource/Pages/ManageTitles.php',
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\IndustryResource\\Pages\\ManageIndustries'      => 'IndustryResource/Pages/ManageIndustries.php',
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\BankResource\\Pages\\ManageBanks'               => 'BankResource/Pages/ManageBanks.php',
            'Webkul\\Contact\\Filament\\Clusters\\Configurations\\Resources\\BankAccountResource\\Pages\\ManageBankAccounts' => 'BankAccountResource/Pages/ManageBankAccounts.php',
        ];

        $basePath = app_path('Overrides/Webkul/Contact/Filament/Clusters/Configurations/Resources/');

        spl_autoload_register(
            function (string $class) use ($pages, $basePath): bool {
                if (! isset($pages[$class])) {
                    return false;
                }

                require $basePath.$pages[$class];

                return true;
            },
            prepend: true,
        );
    }

    protected function registerPartnerResourceOverride(): void
    {
        $overrides = [
            PartnerResource::class => 'PartnerResource.php',
            ManageAddresses::class => 'PartnerResource/Pages/ManageAddresses.php',
            ManageContacts::class  => 'PartnerResource/Pages/ManageContacts.php',
        ];

        spl_autoload_register(
            function (string $class) use ($overrides): bool {
                if (! isset($overrides[$class])) {
                    return false;
                }

                require app_path('Overrides/Webkul/Contact/Filament/Resources/'.$overrides[$class]);

                return true;
            },
            prepend: true,
        );
    }

    protected function ensurePartnerResourceOverrideIsLoaded(): void
    {
        if (class_exists(PartnerResource::class, false)) {
            return;
        }

        require app_path('Overrides/Webkul/Contact/Filament/Resources/PartnerResource.php');
    }

    protected function registerLivewireOverrides(): void
    {
        if (! class_exists(BaseListPartners::class)) {
            return;
        }

        Livewire::component(
            'webkul.contact.filament.resources.partner-resource.pages.list-partners',
            ExtendedListPartners::class,
        );

        Livewire::component(
            BaseListPartners::class,
            ExtendedListPartners::class,
        );
    }
}
