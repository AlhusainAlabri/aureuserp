<?php

use App\Settings\BrandingSettings;
use App\Settings\NavigationSettings;
use App\Settings\OperationsSettings;
use Spatie\LaravelData\Data;
use Spatie\LaravelSettings\SettingsCasts\DataCast;
use Spatie\LaravelSettings\SettingsCasts\DateTimeInterfaceCast;
use Spatie\LaravelSettings\SettingsCasts\DateTimeZoneCast;
use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;
use Spatie\LaravelSettings\SettingsRepositories\RedisSettingsRepository;
use Webkul\Account\Settings\CustomerInvoiceSettings;
use Webkul\Account\Settings\DefaultAccountSettings;
use Webkul\Account\Settings\TaxesSettings;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Inventory\Settings\LogisticSettings;
use Webkul\Inventory\Settings\OperationSettings as InventoryOperationSettings;
use Webkul\Inventory\Settings\TraceabilitySettings;
use Webkul\Inventory\Settings\WarehouseSettings;
use Webkul\Manufacturing\Settings\OperationSettings as ManufacturingOperationSettings;
use Webkul\Manufacturing\Settings\PlanningSettings;
use Webkul\Payroll\Settings\GratuitySettings;
use Webkul\Product\Settings\ProductSettings as CoreProductSettings;
use Webkul\Project\Settings\TaskSettings;
use Webkul\Project\Settings\TimeSettings;
use Webkul\Purchase\Settings\OrderSettings;
use Webkul\Purchase\Settings\ProductSettings as PurchaseProductSettings;
use Webkul\Sale\Settings\InvoiceSettings;
use Webkul\Sale\Settings\PriceSettings;
use Webkul\Sale\Settings\ProductSettings as SaleProductSettings;
use Webkul\Sale\Settings\QuotationAndOrderSettings;
use Webkul\Security\Settings\CurrencySettings;
use Webkul\Security\Settings\UserSettings;
use Webkul\Website\Settings\ContactSettings;

return [

    /*
     * Each settings class used in your application must be registered, you can
     * put them (manually) here.
     */
    'settings' => [
        UserSettings::class,
        CurrencySettings::class,
        CustomerInvoiceSettings::class,
        DefaultAccountSettings::class,
        TaxesSettings::class,
        WarehouseSettings::class,
        InventoryOperationSettings::class,
        LogisticSettings::class,
        TraceabilitySettings::class,
        ManufacturingOperationSettings::class,
        PlanningSettings::class,
        CoreProductSettings::class,
        TaskSettings::class,
        TimeSettings::class,
        OrderSettings::class,
        PurchaseProductSettings::class,
        InvoiceSettings::class,
        PriceSettings::class,
        SaleProductSettings::class,
        QuotationAndOrderSettings::class,
        ContactSettings::class,
        NavigationSettings::class,
        BrandingSettings::class,
        OperationsSettings::class,
        GratuitySettings::class,
        AttendanceSettings::class,
    ],

    /*
     * The path where the settings classes will be created.
     */
    'setting_class_path' => app_path('Settings'),

    /*
     * In these directories settings migrations will be stored and ran when migrating. A settings
     * migration created via the make:settings-migration command will be stored in the first path or
     * a custom defined path when running the command.
     */
    'migrations_paths' => [
        database_path('settings'),
    ],

    /*
     * When no repository was set for a settings class the following repository
     * will be used for loading and saving settings.
     */
    'default_repository' => 'database',

    /*
     * Settings will be stored and loaded from these repositories.
     */
    'repositories' => [
        'database' => [
            'type'       => DatabaseSettingsRepository::class,
            'model'      => null,
            'table'      => null,
            'connection' => null,
        ],
        'redis' => [
            'type'       => RedisSettingsRepository::class,
            'connection' => null,
            'prefix'     => null,
        ],
    ],

    /*
     * The encoder and decoder will determine how settings are stored and
     * retrieved in the database. By default, `json_encode` and `json_decode`
     * are used.
     */
    'encoder' => null,
    'decoder' => null,

    /*
     * The contents of settings classes can be cached through your application,
     * settings will be stored within a provided Laravel store and can have an
     * additional prefix.
     */
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'store'   => null,
        'prefix'  => null,
        'ttl'     => null,
        'memo'    => env('SETTINGS_CACHE_MEMO', true),
    ],

    /*
     * These global casts will be automatically used whenever a property within
     * your settings class isn't a default PHP type.
     */
    'global_casts' => [
        DateTimeInterface::class => DateTimeInterfaceCast::class,
        DateTimeZone::class      => DateTimeZoneCast::class,
        //        Spatie\DataTransferObject\DataTransferObject::class => Spatie\LaravelSettings\SettingsCasts\DtoCast::class,
        Data::class => DataCast::class,
    ],

    /*
     * The package will look for settings in these paths and automatically
     * register them.
     */
    'auto_discover_settings' => [
        app_path('Settings'),
    ],

    /*
     * Automatically discovered settings classes can be cached, so they don't
     * need to be searched each time the application boots up.
     */
    'discovered_settings_cache_path' => base_path('bootstrap/cache'),
];
