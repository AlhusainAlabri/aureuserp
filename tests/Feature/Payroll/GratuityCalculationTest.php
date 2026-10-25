<?php

use App\Models\Hr\EmployeeContract;
use App\Services\Hr\HrExtensionSchemaService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\GratuityFixtures;
use Tests\Helpers\PasiFixtures;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\GratuityTier;
use Webkul\Payroll\Services\BasicSalaryResolver;
use Webkul\Payroll\Services\GratuityCalculator;
use Webkul\Payroll\Services\ServicePeriodResolver;
use Webkul\Payroll\Settings\GratuitySettings;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    app(HrExtensionSchemaService::class)->ensure();
    GratuityFixtures::tiers();
});

function gratuityFor(string $joined, string $departed, ?string $nationality = 'IN', float $basic = 900, ?bool $paysGratuity = null)
{
    $employee = GratuityFixtures::employee(PasiFixtures::company('OM'), $nationality, $joined, $departed, $basic, $paysGratuity);

    return app(GratuityCalculator::class)->calculate($employee, Carbon::parse($joined), Carbon::parse($departed));
}

it('GratuityCalculationTest: exactly three calendar years pay 45 days, with or without a leap day', function (string $joined, string $departed): void {
    $result = gratuityFor($joined, $departed);

    expect($result->amount)->toBe(1350.0)
        ->and($result->ineligibleReason)->toBeNull()
        ->and($result->breakdown)->toHaveCount(1);
})->with([
    'spans 29 Feb 2020' => ['2020-01-01', '2023-01-01'],
    'no leap day'       => ['2021-03-01', '2024-03-01'],
    'starts on 29 Feb'  => ['2020-02-29', '2023-02-28'],
]);

it('GratuityCalculationTest: three years and one day add one day of the second tier', function (): void {
    $result = gratuityFor('2020-01-01', '2023-01-02');

    expect($result->amount)->toBe(round(1350 + round((1 / 365) * 30 * 30, 3), 3))
        ->and($result->breakdown)->toHaveCount(2);
});

it('GratuityCalculationTest: five years pay both tiers', function (): void {
    $result = gratuityFor('2020-01-01', '2025-01-01');

    expect($result->amount)->toBe(3150.0)
        ->and(collect($result->breakdown)->sum('amount'))->toBe(3150.0)
        ->and($result->serviceYears)->toBe(5.0);
});

it('GratuityCalculationTest: a partial year is pro-rated by days', function (): void {
    $result = gratuityFor('2020-01-01', '2022-07-01');

    expect($result->amount)->toBe(round((2 + 181 / 365) * 15 * 30, 3))
        ->and($result->serviceDays)->toBe(912);
});

it('GratuityCalculationTest: below the minimum service pays nothing', function (): void {
    $result = gratuityFor('2022-01-01', '2022-07-01');

    expect($result->amount)->toBe(0.0)
        ->and($result->ineligibleReason)->toBe('below_minimum_service')
        ->and($result->breakdown)->toBe([]);
});

it('GratuityCalculationTest: omani, unknown nationality and non-paying reasons pay nothing', function (?string $nationality, ?bool $paysGratuity, string $reason): void {
    $result = gratuityFor('2018-01-01', '2024-01-01', $nationality, 900, $paysGratuity);

    expect($result->amount)->toBe(0.0)->and($result->ineligibleReason)->toBe($reason);
})->with([
    'omani'                 => ['OM', null, 'not_eligible_category'],
    'unknown nationality'   => [null, null, 'nationality_unknown'],
    'reason does not pay'   => ['IN', false, 'reason_does_not_pay'],
]);

it('GratuityCalculationTest: a departure reason that pays, or no reason at all, is eligible', function (?bool $paysGratuity): void {
    expect(gratuityFor('2018-01-01', '2024-01-01', 'IN', 900, $paysGratuity)->amount)->toBeGreaterThan(0.0);
})->with([[null], [true]]);

it('GratuityCalculationTest: settings change the minimum service and the daily rate', function (): void {
    $settings = app(GratuitySettings::class);
    $settings->minimum_service_years = 2;
    $settings->leave_payout_divisor = 26;
    $settings->save();

    expect(gratuityFor('2022-01-01', '2023-06-01')->ineligibleReason)->toBe('below_minimum_service')
        ->and(gratuityFor('2020-01-01', '2023-01-01')->amount)->toBe(round(45 * 900 / 26, 3));
});

it('GratuityCalculationTest: the tier set in force on the departure date applies', function (): void {
    GratuityTier::query()->update(['effective_to' => '2022-12-31']);
    GratuityTier::query()->create(['from_year' => 0, 'to_year' => null, 'days_per_year' => 20, 'effective_from' => '2023-01-01']);

    expect(gratuityFor('2019-06-01', '2022-12-31')->amount)->toBe(round(1350 + round((213 / 365) * 30 * 30, 3), 3))
        ->and(gratuityFor('2020-01-01', '2024-01-01')->amount)->toBe(round(4 * 20 * 30, 3));
});

it('GratuityCalculationTest: the service period starts at the earliest joining date of any contract', function (): void {
    $employee = GratuityFixtures::employee(PasiFixtures::company('OM'), 'IN', '2021-05-01', '2024-05-01');
    GratuityFixtures::contract($employee, '2019-01-01', '2018-03-15');
    GratuityFixtures::contract($employee, '2017-01-01', null);

    $period = app(ServicePeriodResolver::class)->forEmployee($employee->fresh());

    expect($period['start']->toDateString())->toBe('2018-03-15')
        ->and($period['end']->toDateString())->toBe('2024-05-01');
});

it('GratuityCalculationTest: the service period falls back to the earliest contract start date', function (): void {
    $employee = GratuityFixtures::employee(PasiFixtures::company('OM'), 'IN', '2021-05-01', '2024-05-01');
    EmployeeContract::query()->where('employee_id', $employee->id)->update(['first_joining_date' => null]);
    GratuityFixtures::contract($employee, '2019-01-01', null);

    expect(app(ServicePeriodResolver::class)->forEmployee($employee->fresh())['start']->toDateString())->toBe('2019-01-01');
});

it('GratuityCalculationTest: the service period is null without contracts or a departure date', function (): void {
    $withoutDeparture = GratuityFixtures::employee(PasiFixtures::company('OM'), 'IN', '2020-01-01', null);
    $withoutContract = PasiFixtures::employee(PasiFixtures::company('OM'), 'IN', 900, withPasi: false);
    $withoutContract->update(['departure_date' => '2024-01-01']);

    $resolver = app(ServicePeriodResolver::class);

    expect($resolver->forEmployee($withoutDeparture))->toBeNull()
        ->and($resolver->forEmployee($withoutContract->fresh()))->toBeNull();
});

it('GratuityCalculationTest: the basic salary is the BASIC assignment in force on the date', function (): void {
    $employee = GratuityFixtures::employee(PasiFixtures::company('OM'), 'IN', '2020-01-01', '2024-01-01', 900);
    $basic = PasiFixtures::components()['basic'];

    EmployeeComponent::query()->where('employee_id', $employee->id)->update(['end_date' => '2022-12-31']);
    EmployeeComponent::query()->create(['employee_id' => $employee->id, 'component_id' => $basic->id, 'amount' => 1200, 'start_date' => '2023-01-01']);

    $resolver = app(BasicSalaryResolver::class);

    expect($resolver->forDate($employee, Carbon::parse('2022-06-01')))->toBe(900.0)
        ->and($resolver->forDate($employee, Carbon::parse('2023-06-01')))->toBe(1200.0)
        ->and($resolver->forDate($employee, Carbon::parse('1999-01-01')))->toBe(0.0);
});

it('GratuityCalculationTest: refuses overlapping bands in the same period but not in a later one', function (): void {
    expect(fn () => GratuityTier::query()->create(['from_year' => 2, 'to_year' => 4, 'days_per_year' => 10, 'effective_from' => '2024-01-01']))
        ->toThrow(ValidationException::class);

    GratuityTier::query()->update(['effective_to' => '2029-12-31']);
    $later = GratuityTier::query()->create(['from_year' => 0, 'to_year' => 3, 'days_per_year' => 10, 'effective_from' => '2030-01-01']);

    expect($later->exists)->toBeTrue();

    $later->update(['days_per_year' => 12]);

    expect((float) $later->fresh()->days_per_year)->toBe(12.0);
});
