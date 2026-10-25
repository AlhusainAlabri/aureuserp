<?php

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\PasiFixtures;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Pages\PasiContributionSchedule;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Services\PasiScheduleBuilder;
use Webkul\Payroll\Services\PayrollCalculator;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    PasiFixtures::clearRules();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function pasiSchedulePayslip(Company $company, ?string $nationality, float $basic, PayslipStatus $status, string $civilId): Payslip
{
    $employee = PasiFixtures::employee($company, $nationality, $basic);
    $employee->update(['civil_id' => $civilId]);

    $payslip = app(PayrollCalculator::class)->calculatePayslip($employee, 2027, 3);
    $payslip->update(['status' => $status]);

    return $payslip;
}

function pasiScheduleUser(bool $permitted): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    Permission::findOrCreate('page_payroll_pasi_contribution_schedule', 'web');

    if ($permitted) {
        $user->givePermissionTo('page_payroll_pasi_contribution_schedule');
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

it('PasiScheduleTest: lists only validated and paid payslips with their PASI shares', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, 800, '2000-01-01');
    PasiFixtures::rule(PasiCategory::Expat, 0, 1, null, '2000-01-01');
    $company = PasiFixtures::company('OM');

    pasiSchedulePayslip($company, 'OM', 1000, PayslipStatus::Validated, '11111111');
    pasiSchedulePayslip($company, 'IN', 600, PayslipStatus::Paid, '22222222');
    pasiSchedulePayslip($company, 'OM', 900, PayslipStatus::Draft, '33333333');

    $rows = app(PasiScheduleBuilder::class)->rows(2027, 3)->keyBy('civil_id');

    expect($rows)->toHaveCount(2)
        ->and($rows['11111111']['category'])->toBe(PasiCategory::Omani->getLabel())
        ->and($rows['11111111']['insurable_salary'])->toBe(800.0)
        ->and($rows['11111111']['employee_share'])->toBe(56.0)
        ->and($rows['11111111']['employer_share'])->toBe(92.0)
        ->and($rows['22222222']['category'])->toBe(PasiCategory::Expat->getLabel())
        ->and($rows['22222222']['insurable_salary'])->toBe(600.0)
        ->and($rows['22222222']['employee_share'])->toBe(0.0)
        ->and($rows['22222222']['employer_share'])->toBe(6.0)
        ->and($rows->has('33333333'))->toBeFalse();
});

it('PasiScheduleTest: totals equal the column sums', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2000-01-01');
    $company = PasiFixtures::company('OM');

    pasiSchedulePayslip($company, 'OM', 1000, PayslipStatus::Validated, '11111111');
    pasiSchedulePayslip($company, 'OM', 500, PayslipStatus::Validated, '22222222');

    $builder = app(PasiScheduleBuilder::class);
    $totals = $builder->totals($builder->rows(2027, 3));

    expect($totals)->toBe([
        'insurable_salary' => 1500.0,
        'employee_share'   => 105.0,
        'employer_share'   => 172.5,
    ]);
});

it('PasiScheduleTest: an empty month has no rows and zero totals', function (): void {
    $builder = app(PasiScheduleBuilder::class);
    $rows = $builder->rows(2031, 1);

    expect($rows)->toBeEmpty()
        ->and($builder->totals($rows))->toBe(['insurable_salary' => 0.0, 'employee_share' => 0.0, 'employer_share' => 0.0]);
});

it('PasiScheduleTest: exports a UTF-8 CSV with a header and one row per employee', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2000-01-01');
    $company = PasiFixtures::company('OM');

    pasiSchedulePayslip($company, 'OM', 1000, PayslipStatus::Validated, '11111111');
    pasiSchedulePayslip($company, 'OM', 500, PayslipStatus::Paid, '22222222');

    $csv = app(PasiScheduleBuilder::class)->csv(2027, 3);
    $lines = array_values(array_filter(preg_split('/\r?\n/', ltrim($csv, "\xEF\xBB\xBF"))));

    expect(str_starts_with($csv, "\xEF\xBB\xBF"))->toBeTrue()
        ->and($lines)->toHaveCount(3)
        ->and($csv)->toContain('1000.000')
        ->and($csv)->toContain('70.000')
        ->and($csv)->toContain('115.000');
});

it('PasiScheduleTest: renders and downloads for a permitted user', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2000-01-01');
    pasiSchedulePayslip(PasiFixtures::company('OM'), 'OM', 1000, PayslipStatus::Validated, '11111111');
    pasiScheduleUser(true);

    expect(PasiContributionSchedule::canAccess())->toBeTrue();

    Livewire::test(PasiContributionSchedule::class)
        ->fillForm(['year' => 2027, 'month' => 3])
        ->assertSuccessful()
        ->assertSee('11111111')
        ->callAction('export')
        ->assertFileDownloaded('pasi-schedule-2027-03.csv');
});

it('PasiScheduleTest: the page permission is the one Shield generates for it', function (): void {
    $permissions = FilamentShield::getPages()[PasiContributionSchedule::class]['permissions'];

    expect(array_keys($permissions))->toBe(['page_payroll_pasi_contribution_schedule']);
});

it('PasiScheduleTest: forbids the page without its permission', function (): void {
    pasiScheduleUser(false);

    expect(PasiContributionSchedule::canAccess())->toBeFalse();

    Livewire::test(PasiContributionSchedule::class)->assertForbidden();
});
