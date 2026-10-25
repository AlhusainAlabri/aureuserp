<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\PasiFixtures;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Payroll\Services\PasiCalculator;
use Webkul\Payroll\Services\PasiRuleManager;
use Webkul\Payroll\Services\PayrollCalculator;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    PasiFixtures::clearRules();
});

function pasiPayslip(Employee $employee, int $year = 2027, int $month = 3): Payslip
{
    return app(PayrollCalculator::class)->calculatePayslip($employee, $year, $month);
}

function pasiLineAmount(Payslip $payslip, string $code): ?float
{
    $line = $payslip->lines()->where('code', $code)->first();

    return $line === null ? null : (float) $line->amount;
}

function pasiLineCount(Payslip $payslip, string $code): int
{
    return $payslip->lines()->where('code', $code)->count();
}

function pasiOmaniDefaults(): void
{
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2000-01-01');
    PasiFixtures::rule(PasiCategory::Expat, 0, 0, null, '2000-01-01');
}

it('PasiCalculationTest: deducts the employee share and accrues the employer share for an omani', function (): void {
    pasiOmaniDefaults();

    $payslip = pasiPayslip(PasiFixtures::employee(PasiFixtures::company('OM'), 'OM'));

    expect(pasiLineAmount($payslip, 'PASI_EMP'))->toBe(70.0)
        ->and(pasiLineAmount($payslip, 'PASI_EMR'))->toBe(115.0)
        ->and((float) $payslip->gross_amount)->toBe(1000.0)
        ->and((float) $payslip->deductions_amount)->toBe(70.0)
        ->and((float) $payslip->employer_cost)->toBe(115.0)
        ->and((float) $payslip->net_amount)->toBe(930.0)
        ->and(pasiLineCount($payslip, 'PASI_WARN'))->toBe(0);
});

it('PasiCalculationTest: caps the insurable salary', function (float $basic, float $employeeShare, float $employerShare): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, 800, '2000-01-01');

    $payslip = pasiPayslip(PasiFixtures::employee(PasiFixtures::company('OM'), 'OM', $basic));

    expect(pasiLineAmount($payslip, 'PASI_EMP'))->toBe($employeeShare)
        ->and(pasiLineAmount($payslip, 'PASI_EMR'))->toBe($employerShare);
})->with([
    'above the cap'      => [1000.0, 56.0, 92.0],
    'exactly at the cap' => [800.0, 56.0, 92.0],
    'one baisa above'    => [800.001, 56.0, 92.0],
    'below the cap'      => [500.0, 35.0, 57.5],
]);

it('PasiCalculationTest: the insurable base stops exactly at the cap', function (float $basic, float $expectedBase): void {
    $rule = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, 800, '2000-01-01');

    $payslip = pasiPayslip(PasiFixtures::employee(PasiFixtures::company('OM'), 'OM', $basic));

    expect(app(PasiCalculator::class)->base($payslip, $rule))->toBe($expectedBase);
})->with([
    'one baisa below' => [799.999, 799.999],
    'exactly at'      => [800.0, 800.0],
    'one baisa above' => [800.001, 800.0],
]);

it('PasiCalculationTest: counts only insurable earnings in the base', function (): void {
    pasiOmaniDefaults();

    $housing = SalaryComponent::query()->updateOrCreate(['code' => 'HOUSING'], [
        'name'              => 'Housing Allowance',
        'name_ar'           => 'بدل سكن',
        'type'              => SalaryComponentType::Earning,
        'calculation_type'  => CalculationType::PercentOfBasic,
        'default_percent'   => 25,
        'is_pasi_insurable' => false,
        'is_active'         => true,
        'sort_order'        => 20,
    ]);

    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM');
    EmployeeComponent::query()->create([
        'employee_id'  => $employee->id,
        'component_id' => $housing->id,
        'start_date'   => '2000-01-01',
    ]);

    $payslip = pasiPayslip($employee);
    expect(pasiLineAmount($payslip, 'HOUSING'))->toBe(250.0)
        ->and(pasiLineAmount($payslip, 'PASI_EMP'))->toBe(70.0);

    $housing->update(['is_pasi_insurable' => true]);

    $payslip = pasiPayslip($employee);
    expect(pasiLineAmount($payslip, 'PASI_EMP'))->toBe(87.5)
        ->and(pasiLineAmount($payslip, 'PASI_EMR'))->toBe(143.75);
});

it('PasiCalculationTest: an expat on a zero rule gets no PASI lines and no warning', function (): void {
    pasiOmaniDefaults();

    $payslip = pasiPayslip(PasiFixtures::employee(PasiFixtures::company('OM'), 'IN'));

    expect(pasiLineCount($payslip, 'PASI_EMP'))->toBe(0)
        ->and(pasiLineCount($payslip, 'PASI_EMR'))->toBe(0)
        ->and(pasiLineCount($payslip, 'PASI_WARN'))->toBe(0)
        ->and((float) $payslip->net_amount)->toBe(1000.0);
});

it('PasiCalculationTest: an expat with an employer-only rate gets only the employer line', function (): void {
    PasiFixtures::rule(PasiCategory::Expat, 0, 1, null, '2000-01-01');

    $payslip = pasiPayslip(PasiFixtures::employee(PasiFixtures::company('OM'), 'IN'));

    expect(pasiLineCount($payslip, 'PASI_EMP'))->toBe(0)
        ->and(pasiLineAmount($payslip, 'PASI_EMR'))->toBe(10.0)
        ->and(pasiLineCount($payslip, 'PASI_WARN'))->toBe(0);
});

it('PasiCalculationTest: warns once and takes no PASI when the category or rule is unknown', function (string $case): void {
    if ($case !== 'no rule') {
        pasiOmaniDefaults();
    }

    $employee = match ($case) {
        'no nationality'     => PasiFixtures::employee(PasiFixtures::company('OM'), null),
        'no company country' => PasiFixtures::employee(PasiFixtures::company(null), 'OM'),
        'no rule'            => PasiFixtures::employee(PasiFixtures::company('OM'), 'OM'),
    };

    $payslip = pasiPayslip($employee);

    expect(pasiLineCount($payslip, 'PASI_EMP'))->toBe(0)
        ->and(pasiLineCount($payslip, 'PASI_EMR'))->toBe(0)
        ->and(pasiLineCount($payslip, 'PASI_WARN'))->toBe(1)
        ->and(pasiLineAmount($payslip, 'PASI_WARN'))->toBe(0.0)
        ->and((float) $payslip->net_amount)->toBe(1000.0);
})->with(['no nationality', 'no company country', 'no rule']);

it('PasiCalculationTest: uses the rule in force for each month across a mid-month rate change', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');
    app(PasiRuleManager::class)->addRule(PasiCategory::Omani, 8, 12, null, Carbon::create(2027, 3, 15));

    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM');

    expect(pasiLineAmount(pasiPayslip($employee, 2027, 2), 'PASI_EMP'))->toBe(70.0)
        ->and(pasiLineAmount(pasiPayslip($employee, 2027, 3), 'PASI_EMP'))->toBe(80.0)
        ->and(pasiLineAmount(pasiPayslip($employee, 2027, 2), 'PASI_EMP'))->toBe(70.0);
});

it('PasiCalculationTest: recalculating a draft payslip never duplicates PASI or warning lines', function (): void {
    pasiOmaniDefaults();
    $calculator = app(PayrollCalculator::class);

    $company = PasiFixtures::company('OM');

    $payslip = pasiPayslip(PasiFixtures::employee($company, 'OM'));
    $calculator->recalculatePayslip($payslip);
    $calculator->recalculatePayslip($payslip);

    expect(pasiLineCount($payslip, 'PASI_EMP'))->toBe(1)
        ->and(pasiLineCount($payslip, 'PASI_EMR'))->toBe(1);

    $unknown = pasiPayslip(PasiFixtures::employee($company, null));
    $calculator->recalculatePayslip($unknown);
    $calculator->recalculatePayslip($unknown);

    expect(pasiLineCount($unknown, 'PASI_WARN'))->toBe(1);
});

it('PasiCalculationTest: ignores a percent override on the employee assignment', function (): void {
    pasiOmaniDefaults();

    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM');
    EmployeeComponent::query()
        ->where('employee_id', $employee->id)
        ->whereHas('component', fn ($query) => $query->where('code', 'PASI_EMP'))
        ->update(['percent' => 3]);

    expect(pasiLineAmount(pasiPayslip($employee), 'PASI_EMP'))->toBe(70.0);
});

it('PasiCalculationTest: computes PASI on the pro-rated basic after unpaid leave', function (): void {
    if (! Schema::hasTable('time_off_leaves') || ! Schema::hasColumn('time_off_leave_types', 'unpaid')) {
        $this->markTestSkipped('Time Off is not installed in the test database.');
    }

    pasiOmaniDefaults();
    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM');

    $leaveTypeId = DB::table('time_off_leave_types')->insertGetId([
        'name'       => 'Unpaid PASI test',
        'unpaid'     => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('time_off_leaves')->insert([
        'employee_id'       => $employee->id,
        'holiday_status_id' => $leaveTypeId,
        'state'             => 'validate_two',
        'date_from'         => '2027-03-01 00:00:00',
        'date_to'           => '2027-03-11 23:59:59',
        'number_of_days'    => 11,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $payslip = pasiPayslip($employee);

    expect((float) $payslip->basic_salary)->toBe(500.0)
        ->and(pasiLineAmount($payslip, 'PASI_EMP'))->toBe(35.0)
        ->and(pasiLineAmount($payslip, 'PASI_EMR'))->toBe(57.5);
});
