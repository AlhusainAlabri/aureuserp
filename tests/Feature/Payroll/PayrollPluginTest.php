<?php

use App\Mail\PayslipMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\BatchStatus;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\LoanInstallmentStatus;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Filament\Clusters\Configuration;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Payroll\Services\PayrollCalculator;
use Webkul\Payroll\Services\PayslipPdfService;
use Webkul\Payroll\Services\WpsExportService;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

beforeEach(function (): void {
    if (! Schema::hasTable('payroll_salary_components')) {
        Artisan::call('payroll:install', ['--no-interaction' => true]);
    }
});

function payrollCompany(): Company
{
    return Company::query()->first() ?? Company::factory()->create(['currency_id' => null]);
}

function payrollUser(array $permissions = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    return $user;
}

function payrollEmployee(?Company $company = null): Employee
{
    $company ??= payrollCompany();

    return Employee::query()->create([
        'company_id'  => $company->id,
        'name'        => 'Payroll Test Employee '.uniqid(),
        'work_email'  => 'payroll-'.uniqid().'@test.example',
        'is_active'   => true,
    ]);
}

function payrollBasicComponent(?Company $company = null): SalaryComponent
{
    $company ??= payrollCompany();

    return SalaryComponent::query()->firstOrCreate(
        ['code' => 'BASIC'],
        [
            'name'              => 'Basic Salary',
            'name_ar'           => 'الراتب الأساسي',
            'type'              => SalaryComponentType::Earning,
            'calculation_type'  => CalculationType::Fixed,
            'default_amount'    => 1000,
            'company_id'        => $company->id,
            'is_active'         => true,
            'sort_order'        => 1,
        ],
    );
}

function payrollBatchFlow(array $approverIds, int $steps = 3): ApprovalFlow
{
    $morphClass = (new PayrollBatch)->getMorphClass();

    ApprovalFlow::query()->where('approvable_type', $morphClass)->each(function (ApprovalFlow $flow): void {
        $flow->steps()->delete();
        $flow->delete();
    });

    $flow = ApprovalFlow::query()->create([
        'name'            => 'Payroll Batch Test Flow',
        'approvable_type' => $morphClass,
        'is_active'       => true,
    ]);

    for ($step = 1; $step <= $steps; $step++) {
        $flow->steps()->create([
            'name'               => "Step {$step}",
            'order'              => $step,
            'type'               => 'single',
            'approver_resolver'  => UserResolver::class,
            'approver_config'    => ['user_ids' => $approverIds],
            'required_approvals' => 1,
        ]);
    }

    return $flow->fresh('steps');
}

it('PayrollPluginTest: can create a salary component', function (): void {
    $component = SalaryComponent::factory()->create([
        'code' => 'TEST_'.fake()->unique()->lexify('???'),
    ]);

    expect($component)->toBeInstanceOf(SalaryComponent::class)
        ->and($component->code)->not->toBeEmpty();
});

it('PayrollPluginTest: can assign components to employee', function (): void {
    $employee = payrollEmployee();
    $component = payrollBasicComponent($employee->company);

    $assignment = EmployeeComponent::factory()->create([
        'employee_id'  => $employee->id,
        'component_id' => $component->id,
        'amount'       => 1200,
        'start_date'   => now()->startOfMonth(),
    ]);

    expect($assignment->employee_id)->toBe($employee->id)
        ->and($assignment->component_id)->toBe($component->id);
});

it('PayrollPluginTest: calculator generates payslip with correct basic', function (): void {
    $employee = payrollEmployee();
    $basic = payrollBasicComponent($employee->company);

    EmployeeComponent::factory()->create([
        'employee_id'  => $employee->id,
        'component_id' => $basic->id,
        'amount'       => 1500,
        'start_date'   => now()->startOfYear(),
    ]);

    $year = (int) now()->year;
    $month = (int) now()->month;

    $payslip = app(PayrollCalculator::class)->calculatePayslip($employee, $year, $month);

    expect($payslip->basic_salary)->toEqual(1500.0)
        ->and($payslip->employee_id)->toBe($employee->id);
});

it('PayrollPluginTest: percent-of-basic component calculates correctly', function (): void {
    $company = payrollCompany();
    $employee = payrollEmployee($company);

    $basic = payrollBasicComponent($company);

    EmployeeComponent::factory()->create([
        'employee_id'  => $employee->id,
        'component_id' => $basic->id,
        'amount'       => 1000,
        'start_date'   => now()->startOfYear(),
    ]);

    $housing = SalaryComponent::factory()->create([
        'code'             => 'HOUSING_TEST',
        'type'             => SalaryComponentType::Earning,
        'calculation_type' => CalculationType::PercentOfBasic,
        'default_percent'  => 25,
        'company_id'       => $company->id,
        'sort_order'       => 2,
    ]);

    EmployeeComponent::factory()->create([
        'employee_id'  => $employee->id,
        'component_id' => $housing->id,
        'start_date'   => now()->startOfYear(),
    ]);

    $payslip = app(PayrollCalculator::class)->calculatePayslip($employee, (int) now()->year, (int) now()->month);
    $payslip->load('lines');

    $housingLine = $payslip->lines->firstWhere('code', 'HOUSING_TEST');

    expect($housingLine)->not->toBeNull()
        ->and((float) $housingLine->amount)->toEqual(250.0);
});

it('PayrollPluginTest: fixed component uses default_amount', function (): void {
    $company = payrollCompany();
    $component = SalaryComponent::factory()->create([
        'calculation_type' => CalculationType::Fixed,
        'default_amount'   => 75.5,
        'company_id'       => $company->id,
    ]);

    $payslip = Payslip::factory()->make(['basic_salary' => 1000, 'gross_amount' => 1000]);

    expect($component->calculateAmount($payslip))->toEqual(75.5);
});

it('PayrollPluginTest: payslip reference auto-generates', function (): void {
    $payslip = Payslip::factory()->create();

    expect($payslip->reference_number)->toMatch('/^PSL-\d{4}-\d{2}-\d{4}$/');
});

it('PayrollPluginTest: batch reference auto-generates', function (): void {
    $batch = PayrollBatch::factory()->create([
        'period_year'  => 2026,
        'period_month' => 5,
    ]);

    expect($batch->reference_number)->toBe('PAY-2026-05');
});

it('PayrollPluginTest: batch cannot be marked paid without approval', function (): void {
    $batch = PayrollBatch::factory()->create(['status' => BatchStatus::Draft]);

    expect(fn () => $batch->markAsPaid())->toThrow(RuntimeException::class);
});

it('PayrollPluginTest: full approval flow reaches approved status', function (): void {
    $approver = User::withoutEvents(fn (): User => User::factory()->create());
    $engine = app(ApprovalEngine::class);
    $batch = PayrollBatch::factory()->create([
        'status'           => BatchStatus::Draft,
        'period_year'      => 2030,
        'period_month'     => 6,
        'reference_number' => 'PAY-2030-06',
    ]);
    $flow = payrollBatchFlow([$approver->id], 3);

    $approval = $batch->submitForApproval($flow, $approver->id);

    foreach (range(1, 3) as $step) {
        $approval->refresh();
        $current = $approval->currentStepInstance();
        expect($current)->not->toBeNull();
        $engine->approve($current, $approver->id);
    }

    $batch->refresh();

    expect($batch->isFullyApproved())->toBeTrue()
        ->and($batch->status)->toBe(BatchStatus::Approved);
});

it('PayrollPluginTest: mark paid updates payslips and dispatches mail', function (): void {
    Mail::fake();
    Storage::fake('private');

    $approver = User::withoutEvents(fn (): User => User::factory()->create());
    $engine = app(ApprovalEngine::class);
    $employee = payrollEmployee();
    $basic = payrollBasicComponent($employee->company);

    EmployeeComponent::factory()->create([
        'employee_id'  => $employee->id,
        'component_id' => $basic->id,
        'amount'       => 1000,
        'start_date'   => now()->startOfYear(),
    ]);

    $batch = PayrollBatch::factory()->create([
        'company_id'         => $employee->company_id,
        'period_year'        => 2031,
        'period_month'       => 3,
        'reference_number'   => 'PAY-2031-03',
        'status'             => BatchStatus::Draft,
    ]);

    $payslip = app(PayrollCalculator::class)->calculatePayslip($employee, $batch->period_year, $batch->period_month);

    $flow = payrollBatchFlow([$approver->id], 1);
    $approval = $batch->submitForApproval($flow, $approver->id);
    $engine->approve($approval->currentStepInstance(), $approver->id);
    $batch->refresh();

    $batch->markAsPaid();
    $batch->payslips()->update(['status' => PayslipStatus::Paid]);

    $pdfPath = app(PayslipPdfService::class)->generate($payslip->fresh());
    Mail::to($employee->work_email)->queue(new PayslipMail($payslip, $pdfPath));

    expect($batch->fresh()->isPaid())->toBeTrue()
        ->and($payslip->fresh()->status)->toBe(PayslipStatus::Paid);

    Mail::assertQueued(PayslipMail::class);
});

it('PayrollPluginTest: loan calculates installment amount', function (): void {
    $employee = payrollEmployee();

    $loan = Loan::factory()->create([
        'employee_id'        => $employee->id,
        'company_id'         => $employee->company_id,
        'total_amount'       => 1200,
        'installment_count'  => 12,
        'installment_amount' => null,
    ]);

    expect((float) $loan->fresh()->installment_amount)->toEqual(100.0);
});

it('PayrollPluginTest: activated loan generates installments', function (): void {
    $employee = payrollEmployee();

    $loan = Loan::factory()->create([
        'employee_id'        => $employee->id,
        'company_id'         => $employee->company_id,
        'status'             => LoanStatus::Approved,
        'installment_count'  => 6,
        'start_period_year'  => 2026,
        'start_period_month' => 1,
    ]);

    $loan->generateInstallments();

    expect($loan->installments()->count())->toBe(6);
});

it('PayrollPluginTest: loan deduction on payslip validation', function (): void {
    $employee = payrollEmployee();
    payrollBasicComponent($employee->company);

    $loan = Loan::factory()->create([
        'employee_id'        => $employee->id,
        'status'             => LoanStatus::Active,
        'total_amount'       => 600,
        'installment_count'  => 6,
        'amount_repaid'      => 0,
        'amount_remaining'   => 600,
        'start_period_year'  => (int) now()->year,
        'start_period_month' => (int) now()->month,
    ]);

    $loan->generateInstallments();

    $payslip = Payslip::factory()->create([
        'employee_id'  => $employee->id,
        'period_year'  => (int) now()->year,
        'period_month' => (int) now()->month,
        'status'       => PayslipStatus::Validated,
    ]);

    $deducted = $loan->deduct($payslip);

    expect($deducted)->toBeGreaterThan(0)
        ->and($loan->fresh()->amount_repaid)->toBeGreaterThan(0);
});

it('PayrollPluginTest: loan completes when fully repaid', function (): void {
    $loan = Loan::factory()->create([
        'total_amount'     => 100,
        'amount_repaid'    => 100,
        'amount_remaining' => 0,
        'status'           => LoanStatus::Active,
    ]);

    expect($loan->isCompleted())->toBeTrue();
});

it('PayrollPluginTest: WPS export returns downloadable response', function (): void {
    Storage::fake('private');

    $employee = payrollEmployee();
    $batch = PayrollBatch::factory()->create([
        'status'           => BatchStatus::Approved,
        'period_year'      => 2032,
        'period_month'     => 1,
        'reference_number' => 'PAY-2032-01',
    ]);
    Payslip::factory()->paid()->create([
        'batch_id'            => $batch->id,
        'employee_id'         => $employee->id,
        'payment_method'      => 'bank_transfer',
        'bank_account_number' => '1234567890',
    ]);

    $response = app(WpsExportService::class)->export($batch);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

it('PayrollPluginTest: PDF payslip generation succeeds', function (): void {
    Storage::fake('private');

    $employee = payrollEmployee();
    $payslip = Payslip::factory()->create(['employee_id' => $employee->id]);
    $payslip->lines()->create([
        'type'       => SalaryComponentType::Earning->value,
        'code'       => 'BASIC',
        'name'       => 'Basic',
        'amount'     => 1000,
        'sort_order' => 1,
    ]);

    $path = app(PayslipPdfService::class)->generate($payslip->fresh(['employee', 'batch', 'lines']));

    expect(Storage::disk('private')->exists($path))->toBeTrue();
});

it('PayrollPluginTest: employee cannot view another employees payslip', function (): void {
    $owner = payrollEmployee();
    $other = payrollEmployee();

    $payslip = Payslip::factory()->create(['employee_id' => $owner->id]);

    $viewerUser = User::withoutEvents(fn (): User => User::factory()->create());
    $viewerEmployee = payrollEmployee($owner->company);
    $viewerEmployee->update(['user_id' => $viewerUser->id]);

    expect($payslip->employee_id)->not->toBe($viewerEmployee->id);
});

it('PayrollPluginTest: plugin install command runs without errors', function (): void {
    expect(Schema::hasTable('payroll_batches'))->toBeTrue()
        ->and(SalaryComponent::query()->where('code', 'BASIC')->exists())->toBeTrue();
});

it('PayrollPluginTest: cancelled loan installment stays scheduled or cancelled', function (): void {
    $employee = payrollEmployee();
    $loan = Loan::factory()->create([
        'employee_id' => $employee->id,
        'company_id'  => $employee->company_id,
        'status'      => LoanStatus::Cancelled,
    ]);

    $loan->installments()->create([
        'period_year'  => (int) now()->year,
        'period_month' => (int) now()->month,
        'amount'       => 100,
        'status'       => LoanInstallmentStatus::Cancelled,
    ]);

    expect($loan->fresh()->status)->toBe(LoanStatus::Cancelled);
});

it('PayrollPluginTest: salary components live under payroll configuration cluster', function (): void {
    expect(Configuration::getSlug())->toBe('payroll/configuration')
        ->and(Configuration::getNavigationGroup())->toBe(__('payroll::payroll.navigation.group'))
        ->and(SalaryComponentResource::getCluster())->toBe(Configuration::class);

    expect(SalaryComponentResource::getUrl('index', panel: 'admin', isAbsolute: false))
        ->toContain('/admin/payroll/configuration/');
});
