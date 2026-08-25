<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Account\Database\Seeders\AccountSeeder;
use Webkul\Support\Database\Seeders\CompanySeeder;

class SeederSafetyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCompanySeederOverride();
        $this->registerAccountSeederOverride();
    }

    protected function registerCompanySeederOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== CompanySeeder::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Support/Database/Seeders/CompanySeeder.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerAccountSeederOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== AccountSeeder::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Account/Database/Seeders/AccountSeeder.php');

                return true;
            },
            prepend: true,
        );
    }
}
