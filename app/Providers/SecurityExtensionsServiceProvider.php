<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Security\Filament\Resources\RoleResource;
use Webkul\Security\Filament\Resources\RoleResource\Pages\CreateRole;
use Webkul\Security\Filament\Resources\RoleResource\Pages\EditRole;
use Webkul\Security\Filament\Resources\RoleResource\Pages\ViewRole;

class SecurityExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRoleResourceOverride();
        $this->registerCreateRoleOverride();
        $this->registerEditRoleOverride();
        $this->registerViewRoleOverride();
    }

    public function boot(): void
    {
        //
    }

    protected function registerRoleResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== RoleResource::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Security/Filament/Resources/RoleResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerCreateRoleOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== CreateRole::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Security/Filament/Resources/RoleResource/Pages/CreateRole.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerEditRoleOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== EditRole::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Security/Filament/Resources/RoleResource/Pages/EditRole.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerViewRoleOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== ViewRole::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Security/Filament/Resources/RoleResource/Pages/ViewRole.php');

                return true;
            },
            prepend: true,
        );
    }
}
