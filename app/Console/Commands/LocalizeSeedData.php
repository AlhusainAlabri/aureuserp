<?php

namespace App\Console\Commands;

use App\Services\Install\DefaultTaxProfile;
use App\Services\Install\SeedDataLocalizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('erp:localize-data {locale? : Language of the names (defaults to the application locale)}')]
#[Description('Translate the reference data seeded in English (accounts, journals, units, stages…)')]
class LocalizeSeedData extends Command
{
    public function handle(SeedDataLocalizer $localizer, DefaultTaxProfile $taxes): int
    {
        $locale = (string) ($this->argument('locale') ?: config('app.locale'));

        $taxRows = $taxes->apply($locale);

        if ($locale === 'en') {
            $this->info("Seeded data is already in English; {$taxRows} default tax rows set for the company country.");

            return self::SUCCESS;
        }

        $updated = $localizer->localize($locale);

        $this->info(array_sum($updated)." seeded values translated to [{$locale}] in ".count($updated)." columns; {$taxRows} default tax rows set for the company country.");

        return self::SUCCESS;
    }
}
