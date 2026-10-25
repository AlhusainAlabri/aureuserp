<?php

namespace Webkul\Payroll\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Models\PasiRule;

class PasiRuleManager
{
    /**
     * Add a new dated rule, closing the category's open-ended rule the day before it starts.
     *
     * @throws ValidationException when the new rule would not start after the open rule
     */
    public function addRule(
        PasiCategory $category,
        float $employeeRate,
        float $employerRate,
        ?float $salaryCap,
        CarbonInterface $effectiveFrom,
    ): PasiRule {
        return DB::transaction(function () use ($category, $employeeRate, $employerRate, $salaryCap, $effectiveFrom): PasiRule {
            $open = PasiRule::query()
                ->where('category', $category->value)
                ->whereNull('effective_to')
                ->lockForUpdate()
                ->first();

            if ($open !== null) {
                if ($effectiveFrom->toDateString() <= $open->effective_from->toDateString()) {
                    throw ValidationException::withMessages([
                        'effective_from' => __('payroll::payroll.pasi.overlap'),
                    ]);
                }

                $open->update(['effective_to' => $effectiveFrom->copy()->subDay()->toDateString()]);
            }

            return PasiRule::query()->create([
                'category'       => $category,
                'employee_rate'  => $employeeRate,
                'employer_rate'  => $employerRate,
                'salary_cap'     => $salaryCap,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to'   => null,
                'creator_id'     => Auth::id(),
            ]);
        });
    }
}
