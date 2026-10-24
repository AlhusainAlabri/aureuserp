<?php

namespace App\Console\Commands;

use App\Services\Install\SeedDataLocalizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('erp:localize-data {locale? : Language of the names (defaults to the application locale)}')]
#[Description('Translate the reference data seeded in English (accounts, journals, units, stages…)')]
class LocalizeSeedData extends Command
{
    public function handle(SeedDataLocalizer $localizer): int
    {
        $locale = (string) ($this->argument('locale') ?: config('app.locale'));

        if ($locale === 'en') {
            $this->info('Seeded data is already in English.');

            return self::SUCCESS;
        }

        $updated = $localizer->localize($locale);

        $this->info(array_sum($updated)." seeded values translated to [{$locale}] in ".count($updated).' columns.');

        return self::SUCCESS;
    }
}
