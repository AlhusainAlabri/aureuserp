<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Webkul\Support\Models\Company;

class TestBootstrapHelper
{
    private static bool $isERPInstalled = false;

    /**
     * @var array<string, string>
     */
    private static array $customPlugins = [
        'document-archive' => 'doc_folders',
        'meetings'         => 'meetings',
        'correspondence'   => 'correspondences',
        'my-notes'         => 'notes',
        'projects'         => 'projects_projects',
    ];

    public static function assertSafeTestDatabase(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $isSqliteMemory = $connection === 'sqlite' && in_array($database, [':memory:', ''], true);
        $isTestDatabase = str_ends_with($database, '_test') || $isSqliteMemory;

        if (! $isTestDatabase) {
            throw new RuntimeException(
                "Refusing to run destructive test bootstrap against non-test database [{$database}]. "
                .'Configure nodhum_erp_test via .env.testing (see .env.testing.example).'
            );
        }
    }

    public static function ensurePluginInstalled(string $pluginName): void
    {
        $pluginTables = [
            'projects'    => 'projects_projects',
            'sales'       => 'sales_orders',
            'purchases'   => 'purchases_orders',
            'inventories' => 'inventories_operations',
            'accounts'    => 'accounts_account_moves',
            'products'    => 'products_products',
        ];

        $table = $pluginTables[$pluginName] ?? null;

        if (! $table) {
            throw new InvalidArgumentException("Unknown plugin: {$pluginName}");
        }

        static::ensureERPInstalled();

        if (Schema::hasTable($table)) {
            return;
        }

        Artisan::call("{$pluginName}:install", ['--no-interaction' => true]);

        static::loadPluginRoutes($pluginName);
    }

    public static function ensureERPInstalled(): void
    {
        if (static::$isERPInstalled) {
            return;
        }

        static::assertSafeTestDatabase();

        if (Schema::hasTable('companies') && Company::query()->exists()) {
            Artisan::call('migrate', ['--force' => true]);
            static::ensureCustomPluginsInstalled();
            static::$isERPInstalled = true;

            return;
        }

        Artisan::call('migrate:fresh', ['--force' => true]);

        Artisan::call('erp:install', [
            '--force'          => true,
            '--no-interaction' => true,
            '--admin-name'     => 'Test Admin',
            '--admin-email'    => 'admin@example.com',
            '--admin-password' => 'admin123',
        ]);

        static::ensureCustomPluginsInstalled();

        static::$isERPInstalled = true;
    }

    public static function ensureCustomPluginsInstalled(): void
    {
        static::assertSafeTestDatabase();

        foreach (static::$customPlugins as $plugin => $table) {
            if (Schema::hasTable($table)) {
                continue;
            }

            Artisan::call("{$plugin}:install", ['--no-interaction' => true]);
        }
    }

    /**
     * @param  array<string, string>  $plugins
     */
    public static function ensurePluginsInstalled(array $plugins): void
    {
        static::assertSafeTestDatabase();

        foreach ($plugins as $plugin => $table) {
            if (Schema::hasTable($table)) {
                continue;
            }

            Artisan::call("{$plugin}:install", ['--no-interaction' => true]);
        }
    }

    private static function loadPluginRoutes(string $pluginName): void
    {
        $routeFile = base_path("plugins/webkul/{$pluginName}/routes/api.php");

        if (file_exists($routeFile) && ! app()->routesAreCached()) {
            require $routeFile;
        }
    }
}
