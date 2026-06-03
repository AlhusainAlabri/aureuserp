<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Settings;
use Webkul\Security\Settings\CurrencySettings;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

class ConfigureOmrCurrency extends Command
{
    protected $signature = 'inventory:configure-omr';

    protected $description = 'Set OMR as the default currency for all companies (Oman client)';

    public function handle(): int
    {
        if (! Schema::hasTable('currencies')) {
            $this->warn('Currencies table not found.');

            return self::FAILURE;
        }

        $omr = Currency::query()->where('name', 'OMR')->first();

        if (! $omr) {
            $this->error('OMR currency not found. Run currency seeder first.');

            return self::FAILURE;
        }

        Currency::query()->update(['active' => true]);

        Currency::query()
            ->where('id', '!=', $omr->id)
            ->update(['active' => false]);

        $omr->update([
            'active'         => true,
            'decimal_places' => 3,
            'symbol'         => 'ر.ع.',
        ]);

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'currency_id')) {
            Company::query()->update(['currency_id' => $omr->id]);
            $this->info('Updated default currency for all companies to OMR.');
        }

        if (Schema::hasTable('settings') || class_exists(Settings::class)) {
            try {
                $settingsClass = CurrencySettings::class;

                if (class_exists($settingsClass)) {
                    $settings = app($settingsClass);
                    $settings->default_currency_id = $omr->id;
                    $settings->save();
                    $this->info('Updated application currency settings to OMR.');
                }
            } catch (\Throwable) {
                // Settings class may not exist in all installs.
            }
        }

        $this->info('OMR currency configured successfully.');

        return self::SUCCESS;
    }
}
