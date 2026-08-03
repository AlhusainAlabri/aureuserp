<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

class FeatureTestBootstrap
{
    private static bool $booted = false;

    /**
     * @param  array<string, string>  $plugins  Map of plugin install command => required table name.
     */
    public static function bootstrap(array $plugins = []): void
    {
        require_once base_path('plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php');

        \TestBootstrapHelper::assertSafeTestDatabase();

        if (! static::$booted) {
            static::ensureTestCurrencies();

            if (! Schema::hasTable('companies') || Company::query()->doesntExist()) {
                \TestBootstrapHelper::ensureERPInstalled();
            } else {
                Artisan::call('migrate', ['--force' => true]);
                \TestBootstrapHelper::ensureCustomPluginsInstalled();
            }

            static::$booted = true;
        }

        if ($plugins !== []) {
            \TestBootstrapHelper::ensurePluginsInstalled($plugins);
        }
    }

    private static function ensureTestCurrencies(): void
    {
        if (! Schema::hasTable('currencies')) {
            return;
        }

        foreach ([1, 2, 3] as $index) {
            Currency::query()->firstOrCreate(
                ['iso_numeric' => sprintf('%03d', $index)],
                [
                    'name'           => "Test Currency {$index}",
                    'symbol'         => 'T',
                    'decimal_places' => 2,
                    'full_name'      => "Test Currency {$index}",
                    'rounding'       => 0.01,
                    'active'         => true,
                ],
            );
        }
    }
}
