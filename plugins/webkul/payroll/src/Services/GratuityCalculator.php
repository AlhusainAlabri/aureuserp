<?php

namespace Webkul\Payroll\Services;

use Carbon\CarbonInterface;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\GratuityTier;
use Webkul\Payroll\Settings\GratuitySettings;

class GratuityCalculator
{
    public function __construct(
        protected PasiRuleResolver $categories,
        protected BasicSalaryResolver $basics,
    ) {}

    public function calculate(Employee $employee, CarbonInterface $start, CarbonInterface $end): GratuityResult
    {
        $settings = app(GratuitySettings::class);

        $serviceDays = (int) abs($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));
        $serviceYears = $this->serviceYears($start, $end);

        $reason = $this->ineligibleReason($employee, $settings, $serviceYears);

        if ($reason !== null) {
            return new GratuityResult(0.0, [], $reason, $serviceYears, $serviceDays);
        }

        $dailyRate = $this->basics->forDate($employee, $end) / max($settings->leave_payout_divisor, 1);

        $rows = [];

        foreach (GratuityTier::query()->inForce($end)->orderBy('from_year')->get() as $tier) {
            $from = (float) $tier->from_year;
            $to = $tier->to_year === null ? null : (float) $tier->to_year;
            $years = max(0.0, min($serviceYears, $to ?? $serviceYears) - $from);

            if ($years <= 0.0) {
                continue;
            }

            $days = $years * (float) $tier->days_per_year;

            $rows[] = [
                'from'   => $from,
                'to'     => $to,
                'years'  => round($years, 4),
                'days'   => round($days, 4),
                'amount' => round($days * $dailyRate, 3),
            ];
        }

        return new GratuityResult(
            round(array_sum(array_column($rows, 'amount')), 3),
            $rows,
            null,
            $serviceYears,
            $serviceDays,
        );
    }

    /**
     * Whole calendar years plus the remaining days over 365 (three calendar years is exactly 3.0).
     */
    public function serviceYears(CarbonInterface $start, CarbonInterface $end): float
    {
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->startOfDay();

        if ($to->lessThanOrEqualTo($from)) {
            return 0.0;
        }

        $wholeYears = max($to->year - $from->year, 0);

        while ($wholeYears > 0 && $from->copy()->addYearsNoOverflow($wholeYears)->greaterThan($to)) {
            $wholeYears--;
        }

        $remainingDays = (int) abs($from->copy()->addYearsNoOverflow($wholeYears)->diffInDays($to));

        return $wholeYears + $remainingDays / 365;
    }

    protected function ineligibleReason(Employee $employee, GratuitySettings $settings, float $serviceYears): ?string
    {
        $category = $this->categories->categoryFor($employee);

        if ($category === null) {
            return 'nationality_unknown';
        }

        if ($category->value !== $settings->eligible_category) {
            return 'not_eligible_category';
        }

        if ($employee->departureReason !== null && $employee->departureReason->pays_gratuity === false) {
            return 'reason_does_not_pay';
        }

        if ($serviceYears < $settings->minimum_service_years) {
            return 'below_minimum_service';
        }

        return null;
    }
}
