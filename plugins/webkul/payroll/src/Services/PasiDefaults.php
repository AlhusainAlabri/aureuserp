<?php

namespace Webkul\Payroll\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Models\SalaryComponent;

class PasiDefaults
{
    public const string SEED_EFFECTIVE_FROM = '2000-01-01';

    public static function seedRules(): void
    {
        if (! Schema::hasTable('payroll_pasi_rules')) {
            return;
        }

        $defaults = [
            PasiCategory::Omani->value => [7.00, 11.50],
            PasiCategory::Expat->value => [0.00, 0.00],
        ];

        foreach ($defaults as $category => [$employeeRate, $employerRate]) {
            if (PasiRule::query()->where('category', $category)->exists()) {
                continue;
            }

            PasiRule::query()->create([
                'category'       => $category,
                'employee_rate'  => $employeeRate,
                'employer_rate'  => $employerRate,
                'salary_cap'     => null,
                'effective_from' => self::SEED_EFFECTIVE_FROM,
                'effective_to'   => null,
            ]);
        }
    }

    /**
     * Switch the seeded PASI components to the rule-driven calculation types. A percentage someone
     * customised on the old flat component is carried into the untouched seeded Omani rule.
     */
    public static function convertSeededComponents(): void
    {
        if (! Schema::hasTable('payroll_salary_components') || ! Schema::hasColumn('payroll_salary_components', 'is_pasi_insurable')) {
            return;
        }

        SalaryComponent::query()->where('code', 'BASIC')->update(['is_pasi_insurable' => true]);

        $conversions = [
            'PASI_EMP' => [CalculationType::PasiEmployee, 'employee_rate'],
            'PASI_EMR' => [CalculationType::PasiEmployer, 'employer_rate'],
        ];

        foreach ($conversions as $code => [$newType, $rateColumn]) {
            $component = SalaryComponent::query()
                ->where('code', $code)
                ->where('calculation_type', CalculationType::PercentOfBasic->value)
                ->first();

            if ($component === null) {
                continue;
            }

            if ($component->default_percent !== null && Schema::hasTable('payroll_pasi_rules')) {
                PasiRule::query()
                    ->where('category', PasiCategory::Omani->value)
                    ->whereDate('effective_from', self::SEED_EFFECTIVE_FROM)
                    ->whereNull('effective_to')
                    ->update([$rateColumn => (float) $component->default_percent]);
            }

            $component->update([
                'calculation_type' => $newType,
                'default_percent'  => null,
            ]);
        }
    }
}
