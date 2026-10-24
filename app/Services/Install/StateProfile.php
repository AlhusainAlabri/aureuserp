<?php

namespace App\Services\Install;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the governorates of the company's country when the reference data ships without any
 * (Oman has none), so addresses can be completed from a list.
 */
class StateProfile
{
    /**
     * @var array<string, array<int, array{code: string, en: string, ar: string}>>
     */
    protected const STATES = [
        'OM' => [
            ['code' => 'MA', 'en' => 'Muscat', 'ar' => 'مسقط'],
            ['code' => 'ZU', 'en' => 'Dhofar', 'ar' => 'ظفار'],
            ['code' => 'MU', 'en' => 'Musandam', 'ar' => 'مسندم'],
            ['code' => 'BU', 'en' => 'Al Buraimi', 'ar' => 'البريمي'],
            ['code' => 'DA', 'en' => 'Ad Dakhiliyah', 'ar' => 'الداخلية'],
            ['code' => 'BS', 'en' => 'North Al Batinah', 'ar' => 'شمال الباطنة'],
            ['code' => 'BJ', 'en' => 'South Al Batinah', 'ar' => 'جنوب الباطنة'],
            ['code' => 'SS', 'en' => 'North Ash Sharqiyah', 'ar' => 'شمال الشرقية'],
            ['code' => 'SJ', 'en' => 'South Ash Sharqiyah', 'ar' => 'جنوب الشرقية'],
            ['code' => 'ZA', 'en' => 'Ad Dhahirah', 'ar' => 'الظاهرة'],
            ['code' => 'WU', 'en' => 'Al Wusta', 'ar' => 'الوسطى'],
        ],
    ];

    /**
     * @return int number of states added
     */
    public function apply(string $locale, ?string $countryCode = null): int
    {
        if (! Schema::hasTable('states') || ! Schema::hasTable('countries')) {
            return 0;
        }

        $countryCode = strtoupper((string) ($countryCode ?? $this->companyCountryCode()));
        $states = self::STATES[$countryCode] ?? null;

        if ($states === null) {
            return 0;
        }

        $countryId = DB::table('countries')->where('code', $countryCode)->value('id');

        if (! $countryId || DB::table('states')->where('country_id', $countryId)->exists()) {
            return 0;
        }

        $now = now();

        DB::table('states')->insert(array_map(fn (array $state): array => [
            'country_id' => $countryId,
            'name'       => $locale === 'ar' ? $state['ar'] : $state['en'],
            'code'       => $state['code'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $states));

        return count($states);
    }

    protected function companyCountryCode(): ?string
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        return DB::table('companies')
            ->join('countries', 'countries.id', '=', 'companies.country_id')
            ->orderBy('companies.id')
            ->value('countries.code');
    }
}
