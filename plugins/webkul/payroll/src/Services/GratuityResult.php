<?php

namespace Webkul\Payroll\Services;

/**
 * @phpstan-type BreakdownRow array{from: float, to: ?float, years: float, days: float, amount: float}
 */
final readonly class GratuityResult
{
    /**
     * @param  list<array{from: float, to: ?float, years: float, days: float, amount: float}>  $breakdown
     * @param  ?string  $ineligibleReason  not_eligible_category|nationality_unknown|reason_does_not_pay|below_minimum_service
     */
    public function __construct(
        public float $amount,
        public array $breakdown,
        public ?string $ineligibleReason,
        public float $serviceYears,
        public int $serviceDays,
    ) {}
}
