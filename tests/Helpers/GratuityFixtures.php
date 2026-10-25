<?php

namespace Tests\Helpers;

use App\Enums\Hr\ContractType;
use App\Models\Hr\EmployeeContract;
use Webkul\Employee\Models\DepartureReason;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\GratuityTier;
use Webkul\Payroll\Services\GratuityDefaults;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class GratuityFixtures
{
    /**
     * An employee with one contract starting on $joined, optionally departed, with a BASIC assignment.
     */
    public static function employee(
        Company $company,
        ?string $nationality,
        string $joined,
        ?string $departed,
        float $basic = 900,
        ?bool $paysGratuity = null,
    ): Employee {
        $employee = PasiFixtures::employee($company, $nationality, $basic, withPasi: false);

        $attributes = ['departure_date' => $departed];

        if ($paysGratuity !== null) {
            $attributes['departure_reason_id'] = DepartureReason::query()->create([
                'name'            => 'Gratuity test reason '.uniqid(),
                'pays_gratuity'   => $paysGratuity,
            ])->id;
        }

        $employee->update($attributes);

        EmployeeContract::query()->create([
            'employee_id'        => $employee->id,
            'contract_type'      => ContractType::Permanent,
            'start_date'         => $joined,
            'first_joining_date' => $joined,
            'wage'               => $basic,
            'is_active'          => true,
            'creator_id'         => static::creatorId(),
        ]);

        return $employee->fresh();
    }

    public static function contract(Employee $employee, ?string $startDate, ?string $firstJoiningDate): EmployeeContract
    {
        return EmployeeContract::query()->create([
            'employee_id'        => $employee->id,
            'contract_type'      => ContractType::FixedTerm,
            'start_date'         => $startDate ?? '2000-01-01',
            'first_joining_date' => $firstJoiningDate,
            'is_active'          => true,
            'creator_id'         => static::creatorId(),
        ]);
    }

    /**
     * Replaces every tier with the shipped defaults (rolled back with the test transaction).
     */
    public static function tiers(): void
    {
        GratuityTier::query()->delete();
        GratuityDefaults::seedTiers();
    }

    protected static function creatorId(): int
    {
        return auth()->id() ?? User::withoutEvents(fn (): User => User::factory()->create())->id;
    }
}
