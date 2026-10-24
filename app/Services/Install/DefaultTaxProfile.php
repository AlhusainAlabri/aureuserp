<?php

namespace App\Services\Install;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;
use Throwable;
use Webkul\Account\Settings\TaxesSettings;

/**
 * Sets the seeded default sales/purchase taxes to the standard VAT rate of the
 * company's country, so a new customer does not start with another country's rate.
 *
 * Only the untouched seeded rows are changed (name "15 %"); taxes a customer
 * has created or edited are left alone.
 */
class DefaultTaxProfile
{
    protected const SEEDED_TAX_NAME = '15 %';

    protected const SEEDED_GROUP_NAME = 'Tax 15%';

    /**
     * @return int number of rows changed
     */
    public function apply(string $locale, ?string $countryCode = null): int
    {
        if (! Schema::hasTable('accounts_taxes') || ! Schema::hasTable('accounts_tax_groups')) {
            return 0;
        }

        $countryCode = strtoupper((string) ($countryCode ?? $this->companyCountryCode()));
        $rate = config("erp.vat_rates.{$countryCode}");

        if ($rate === null) {
            return 0;
        }

        $countryId = DB::table('countries')->where('code', $countryCode)->value('id');
        $label = $locale === 'ar' ? "ضريبة القيمة المضافة {$rate}%" : "VAT {$rate}%";

        $changed = DB::table('accounts_taxes')
            ->where('name', self::SEEDED_TAX_NAME)
            ->update([
                'name'          => $label,
                'amount'        => $rate,
                'invoice_label' => $label,
                'description'   => null,
                'formula'       => null,
                'country_id'    => $countryId,
            ]);

        $changed += DB::table('accounts_tax_groups')
            ->where('name', self::SEEDED_GROUP_NAME)
            ->update(['name' => $label]);

        $changed += $this->alignFiscalCountry($countryId);

        return $changed;
    }

    /**
     * The accounting settings ship with the United States as the fiscal country; follow the company's country.
     */
    protected function alignFiscalCountry(?int $countryId): int
    {
        if (! $countryId || ! class_exists(TaxesSettings::class) || ! Schema::hasTable('settings')) {
            return 0;
        }

        $unitedStates = DB::table('countries')->where('code', 'US')->value('id');

        $updated = DB::table('settings')
            ->where('group', 'accounts_taxes')
            ->where('name', 'account_fiscal_country_id')
            ->where('payload', json_encode((int) $unitedStates))
            ->update(['payload' => json_encode($countryId)]);

        if ($updated) {
            app(SettingsCacheFactory::class)->build()->clear();
        }

        return $updated;
    }

    /**
     * The company default tax chosen in the accounting settings, as the id list a tax select expects.
     *
     * @return array<int, int>
     */
    public static function defaultTaxIds(string $setting): array
    {
        if (! class_exists(TaxesSettings::class) || ! Schema::hasTable('settings')) {
            return [];
        }

        try {
            $id = app(TaxesSettings::class)->{$setting} ?? null;
        } catch (Throwable) {
            return [];
        }

        return $id && DB::table('accounts_taxes')->where('id', $id)->where('is_active', true)->exists() ? [(int) $id] : [];
    }

    protected function companyCountryCode(): ?string
    {
        if (! Schema::hasTable('companies') || ! Schema::hasTable('countries')) {
            return null;
        }

        return DB::table('companies')
            ->join('countries', 'countries.id', '=', 'companies.country_id')
            ->orderBy('companies.id')
            ->value('countries.code');
    }
}
