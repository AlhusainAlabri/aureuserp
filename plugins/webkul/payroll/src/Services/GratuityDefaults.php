<?php

namespace Webkul\Payroll\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Models\GratuityTier;

class GratuityDefaults
{
    public const string SEED_EFFECTIVE_FROM = '2000-01-01';

    /**
     * Default tiers: 15 days a year for the first 3 years, 30 days a year after. Not verified against
     * the labour law; HR edits them on the tiers screen. Skipped when any tier already exists.
     */
    public static function seedTiers(): void
    {
        if (! Schema::hasTable('payroll_gratuity_tiers') || GratuityTier::query()->exists()) {
            return;
        }

        foreach ([[0, 3, 15], [3, null, 30]] as [$from, $to, $days]) {
            GratuityTier::query()->create([
                'from_year'      => $from,
                'to_year'        => $to,
                'days_per_year'  => $days,
                'effective_from' => self::SEED_EFFECTIVE_FROM,
                'effective_to'   => null,
            ]);
        }
    }
}
