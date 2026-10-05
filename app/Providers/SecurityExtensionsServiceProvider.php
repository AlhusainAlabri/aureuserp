<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Security\Filament\Resources\CoreTeamResource;
use Webkul\Security\Filament\Resources\CoreUserResource;
use Webkul\Security\Filament\Resources\RoleResource;
use Webkul\Security\Filament\Resources\RoleResource\Pages\CreateRole;
use Webkul\Security\Filament\Resources\RoleResource\Pages\EditRole;
use Webkul\Security\Filament\Resources\RoleResource\Pages\ViewRole;
use Webkul\Security\Filament\Resources\TeamResource;
use Webkul\Security\Filament\Resources\UserResource;

class SecurityExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerUserResourceOverride();
        $this->registerTeamResourceOverride();
        $this->registerRoleResourceOverride();
        $this->registerCreateRoleOverride();
        $this->registerEditRoleOverride();
        $this->registerViewRoleOverride();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('security-extensions'), 'security-extensions');
    }

    protected function registerUserResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class === CoreUserResource::class) {
                    $this->ensureCoreUserResourceIsLoaded();

                    return true;
                }

                if ($class !== UserResource::class) {
                    return false;
                }

                $this->ensureCoreUserResourceIsLoaded();

                require app_path('Overrides/Webkul/Security/Filament/Resources/UserResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreUserResourceIsLoaded(): void
    {
        if (class_exists(CoreUserResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/security/src/Filament/Resources/UserResource.php');
        $cachePath = storage_path('framework/cache/core_security_user_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass UserResource\b/', 'class CoreUserResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function registerTeamResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class === CoreTeamResource::class) {
                    $this->ensureCoreTeamResourceIsLoaded();

                    return true;
                }

                if ($class !== TeamResource::class) {
                    return false;
                }

                $this->ensureCoreTeamResourceIsLoaded();

                require app_path('Overrides/Webkul/Security/Filament/Resources/TeamResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreTeamResourceIsLoaded(): void
    {
        if (class_exists(CoreTeamResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/security/src/Filament/Resources/TeamResource.php');
        $cachePath = storage_path('framework/cache/core_security_team_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass TeamResource\b/', 'class CoreTeamResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
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
