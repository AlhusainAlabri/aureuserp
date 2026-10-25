<?php

use App\Services\Hr\HrExtensionSchemaService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\GratuityFixtures;
use Tests\Helpers\PasiFixtures;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Database\Seeders\EndOfServiceApprovalFlowSeeder;
use Webkul\Payroll\Enums\LoanInstallmentStatus;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Services\FinalSettlementBuilder;
use Webkul\Payroll\Services\LeaveBalanceResolver;
use Webkul\Payroll\Services\PayrollCalculator;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Enums\ApprovalStatus;
use Wezlo\FilamentApproval\Models\Approval;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    app(HrExtensionSchemaService::class)->ensure();
    GratuityFixtures::tiers();
    ApprovalFlow::query()->where('approvable_type', (new EndOfServiceSettlement)->getMorphClass())->each(function (ApprovalFlow $flow): void {
        $flow->steps()->delete();
        $flow->delete();
    });
});

function settlementEmployee(string $nationality = 'IN', string $departed = '2023-01-16', string $joined = '2020-01-01', float $basic = 900): Employee
{
    return GratuityFixtures::employee(PasiFixtures::company('OM'), $nationality, $joined, $departed, $basic);
}

function settlementLoan(Employee $employee, float $remaining, LoanStatus $status = LoanStatus::Active): Loan
{
    return Loan::query()->create([
        'reference_number'   => 'LOAN-T-'.uniqid(),
        'employee_id'        => $employee->id,
        'loan_type'          => 'salary_advance',
        'total_amount'       => $remaining + 300,
        'installment_count'  => 3,
        'start_period_year'  => 2022,
        'start_period_month' => 10,
        'status'             => $status,
        'amount_repaid'      => 300,
        'amount_remaining'   => $remaining,
        'company_id'         => $employee->company_id,
    ]);
}

function gratuityFlow(User $approver): ApprovalFlow
{
    $flow = ApprovalFlow::query()->create([
        'name'            => 'Settlement test flow',
        'approvable_type' => (new EndOfServiceSettlement)->getMorphClass(),
        'is_active'       => true,
    ]);

    $flow->steps()->create([
        'name'               => 'Step 1',
        'order'              => 1,
        'type'               => 'single',
        'approver_resolver'  => UserResolver::class,
        'approver_config'    => ['user_ids' => [$approver->id]],
        'required_approvals' => 1,
    ]);

    return $flow->fresh('steps');
}

it('GratuitySettlementTest: builds a draft settlement with every component', function (): void {
    $employee = settlementEmployee();

    $settlement = app(FinalSettlementBuilder::class)->build($employee);

    $expectedGratuity = round(1350 + round((16 / 365) * 900, 3), 3);
    $expectedFinalSalary = round(900 * 16 / 31, 3);

    expect($settlement->status)->toBe(SettlementStatus::Draft)
        ->and($settlement->join_date->toDateString())->toBe('2020-01-01')
        ->and($settlement->departure_date->toDateString())->toBe('2023-01-16')
        ->and($settlement->service_days)->toBe((int) Carbon::parse('2020-01-01')->diffInDays(Carbon::parse('2023-01-17')))
        ->and((float) $settlement->last_basic)->toBe(900.0)
        ->and((float) $settlement->gratuity_amount)->toBe($expectedGratuity)
        ->and($settlement->gratuity_breakdown['reason'])->toBeNull()
        ->and($settlement->gratuity_breakdown['rows'])->toHaveCount(2)
        ->and((float) $settlement->final_salary_amount)->toBe($expectedFinalSalary)
        ->and((float) $settlement->loan_offset_amount)->toBe(0.0)
        ->and((float) $settlement->net_amount)->toBe(round($expectedGratuity + $expectedFinalSalary, 3))
        ->and($settlement->company_id)->toBe($employee->company_id);
});

it('GratuitySettlementTest: an already paid or validated payslip for the departure month means no final salary', function (PayslipStatus $status): void {
    $employee = settlementEmployee(departed: '2023-01-16');
    $payslip = app(PayrollCalculator::class)->calculatePayslip($employee, 2023, 1);
    $payslip->update(['status' => $status]);

    $settlement = app(FinalSettlementBuilder::class)->build($employee);

    expect((float) $settlement->final_salary_amount)->toBe(0.0);
})->with([PayslipStatus::Validated, PayslipStatus::Paid]);

it('GratuitySettlementTest: a draft payslip does not stop the final salary', function (): void {
    $employee = settlementEmployee(departed: '2023-01-16');
    app(PayrollCalculator::class)->calculatePayslip($employee, 2023, 1);

    expect((float) app(FinalSettlementBuilder::class)->build($employee)->final_salary_amount)->toBe(round(900 * 16 / 31, 3));
});

it('GratuitySettlementTest: pays out the unused balance of paid leave types that need an allocation', function (): void {
    if (! Schema::hasTable('time_off_leave_allocations') || ! Schema::hasColumn('time_off_leave_types', 'unpaid')) {
        $this->markTestSkipped('Time Off is not installed in the test database.');
    }

    $employee = settlementEmployee();
    $type = fn (string $requires, bool $unpaid): int => DB::table('time_off_leave_types')->insertGetId([
        'name' => 'Settlement test '.uniqid(), 'requires_allocation' => $requires, 'unpaid' => $unpaid, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $annual = $type('yes', false);
    $unpaid = $type('yes', true);
    $free = $type('no', false);

    foreach ([[$annual, 21], [$unpaid, 10], [$free, 5]] as [$typeId, $days]) {
        DB::table('time_off_leave_allocations')->insert([
            'holiday_status_id' => $typeId, 'employee_id' => $employee->id, 'state' => 'validate_two',
            'date_from'         => '2022-01-01 00:00:00', 'number_of_days' => $days, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    DB::table('time_off_leaves')->insert([
        'employee_id' => $employee->id, 'holiday_status_id' => $annual, 'state' => 'validate_two',
        'date_from'   => '2022-06-01 00:00:00', 'date_to' => '2022-06-06 23:59:59', 'number_of_days' => 6, 'created_at' => now(), 'updated_at' => now(),
    ]);

    expect(app(LeaveBalanceResolver::class)->unusedDays($employee, Carbon::parse('2023-01-16')))->toBe(15.0);

    $settlement = app(FinalSettlementBuilder::class)->build($employee);

    expect((float) $settlement->unused_leave_days)->toBe(15.0)
        ->and((float) $settlement->leave_payout_amount)->toBe(450.0);
});

it('GratuitySettlementTest: no allocations means no leave payout', function (): void {
    $settlement = app(FinalSettlementBuilder::class)->build(settlementEmployee());

    expect((float) $settlement->unused_leave_days)->toBe(0.0)
        ->and((float) $settlement->leave_payout_amount)->toBe(0.0);
});

it('GratuitySettlementTest: offsets the remaining balance of active loans only', function (): void {
    $employee = settlementEmployee();
    settlementLoan($employee, 200);
    settlementLoan($employee, 150);
    settlementLoan($employee, 999, LoanStatus::Completed);

    $settlement = app(FinalSettlementBuilder::class)->build($employee);

    expect((float) $settlement->loan_offset_amount)->toBe(350.0)
        ->and((float) $settlement->net_amount)->toBe(round((float) $settlement->gratuity_amount + (float) $settlement->final_salary_amount - 350, 3));
});

it('GratuitySettlementTest: a loan larger than everything owed gives a negative net and is still settled on payment', function (): void {
    $employee = settlementEmployee();
    $loan = settlementLoan($employee, 5000);
    $loan->generateInstallments();

    $settlement = app(FinalSettlementBuilder::class)->build($employee);

    expect((float) $settlement->net_amount)->toBeLessThan(0.0);

    $settlement->markAsPaid();

    $loan->refresh();

    expect($settlement->fresh()->status)->toBe(SettlementStatus::Paid)
        ->and($loan->status)->toBe(LoanStatus::Completed)
        ->and((float) $loan->amount_remaining)->toBe(0.0)
        ->and((float) $loan->amount_repaid)->toBe((float) $loan->total_amount)
        ->and($loan->installments()->where('status', '!=', LoanInstallmentStatus::Cancelled)->count())->toBe(0);
});

it('GratuitySettlementTest: an ineligible employee still gets leave payout and final salary', function (string $nationality, ?string $nationalityCode, string $reason): void {
    $employee = settlementEmployee($nationalityCode ?? 'OM');

    if ($nationalityCode === null) {
        $employee->update(['country_id' => null]);
    }

    $settlement = app(FinalSettlementBuilder::class)->build($employee->fresh());

    expect((float) $settlement->gratuity_amount)->toBe(0.0)
        ->and($settlement->gratuity_breakdown['reason'])->toBe($reason)
        ->and($settlement->gratuity_breakdown['rows'])->toBe([])
        ->and((float) $settlement->final_salary_amount)->toBe(round(900 * 16 / 31, 3))
        ->and((float) $settlement->net_amount)->toBe((float) $settlement->final_salary_amount);
})->with([
    'omani'               => ['omani', 'OM', 'not_eligible_category'],
    'unknown nationality' => ['unknown', null, 'nationality_unknown'],
]);

it('GratuitySettlementTest: refuses without a departure date or a joining date and creates nothing', function (): void {
    $withoutDeparture = settlementEmployee();
    $withoutDeparture->update(['departure_date' => null]);

    $withoutContract = PasiFixtures::employee(PasiFixtures::company('OM'), 'IN', 900, withPasi: false);
    $withoutContract->update(['departure_date' => '2023-01-16']);

    $builder = app(FinalSettlementBuilder::class);

    expect(fn () => $builder->build($withoutDeparture->fresh()))->toThrow(RuntimeException::class, __('payroll::exceptions.settlement_no_departure_date'))
        ->and(fn () => $builder->build($withoutContract->fresh()))->toThrow(RuntimeException::class, __('payroll::exceptions.settlement_no_joining_date'))
        ->and(EndOfServiceSettlement::query()->count())->toBe(0);
});

it('GratuitySettlementTest: allows one live settlement per employee and a new one after cancelling', function (): void {
    $employee = settlementEmployee();
    $builder = app(FinalSettlementBuilder::class);

    $first = $builder->build($employee);

    expect(fn () => $builder->build($employee))->toThrow(RuntimeException::class, __('payroll::exceptions.settlement_exists'));

    $first->cancel();

    $second = $builder->build($employee);

    expect($first->fresh()->status)->toBe(SettlementStatus::Cancelled)
        ->and($second->status)->toBe(SettlementStatus::Draft)
        ->and(EndOfServiceSettlement::query()->count())->toBe(2);
});

it('GratuitySettlementTest: pays without approval when no flow is active', function (): void {
    $settlement = app(FinalSettlementBuilder::class)->build(settlementEmployee());

    expect($settlement->canBePaid())->toBeTrue();

    $settlement->markAsPaid();

    expect($settlement->fresh()->status)->toBe(SettlementStatus::Paid)
        ->and($settlement->fresh()->paid_at)->not->toBeNull();
});

it('GratuitySettlementTest: an active flow blocks payment until the settlement is approved', function (): void {
    $approver = User::withoutEvents(fn (): User => User::factory()->create());
    $flow = gratuityFlow($approver);
    $settlement = app(FinalSettlementBuilder::class)->build(settlementEmployee());

    expect($settlement->canBePaid())->toBeFalse()
        ->and(fn () => $settlement->markAsPaid())->toThrow(RuntimeException::class, __('payroll::exceptions.settlement_cannot_be_paid'));

    $approval = $settlement->submitForApproval($flow, $approver->id);

    expect($settlement->fresh()->status)->toBe(SettlementStatus::PendingApproval)
        ->and($settlement->fresh()->canBePaid())->toBeFalse();

    app(ApprovalEngine::class)->approve($approval->fresh()->currentStepInstance(), $approver->id);

    $settlement->refresh();

    expect($settlement->status)->toBe(SettlementStatus::Approved)
        ->and($settlement->approved_at)->not->toBeNull()
        ->and($settlement->canBePaid())->toBeTrue();

    $settlement->markAsPaid();

    expect($settlement->fresh()->status)->toBe(SettlementStatus::Paid);
});

it('GratuitySettlementTest: a paid settlement cannot be paid again, cancelled or edited', function (): void {
    $settlement = app(FinalSettlementBuilder::class)->build(settlementEmployee());
    $settlement->markAsPaid();
    $settlement->refresh();

    expect(fn () => $settlement->markAsPaid())->toThrow(RuntimeException::class)
        ->and(fn () => $settlement->cancel())->toThrow(RuntimeException::class, __('payroll::exceptions.settlement_cannot_be_cancelled'))
        ->and(fn () => $settlement->update(['other_adjustment' => 50]))->toThrow(RuntimeException::class, __('payroll::exceptions.settlement_locked'))
        ->and($settlement->isEditable())->toBeFalse();
});

it('GratuitySettlementTest: editing the unused leave days recomputes the payout and the net', function (): void {
    $settlement = app(FinalSettlementBuilder::class)->build(settlementEmployee());
    $before = (float) $settlement->net_amount;

    $settlement->update(['unused_leave_days' => 10, 'other_adjustment' => -25]);
    $settlement->refresh();

    expect((float) $settlement->leave_payout_amount)->toBe(300.0)
        ->and((float) $settlement->net_amount)->toBe(round($before + 300 - 25, 3));
});

it('GratuitySettlementTest: seeds one inactive approval flow, once', function (): void {
    (new EndOfServiceApprovalFlowSeeder)->run();
    (new EndOfServiceApprovalFlowSeeder)->run();

    $flows = ApprovalFlow::query()->where('approvable_type', (new EndOfServiceSettlement)->getMorphClass())->get();

    expect($flows)->toHaveCount(1)
        ->and($flows->first()->is_active)->toBeFalse()
        ->and($flows->first()->steps)->toHaveCount(3);
});

it('GratuitySettlementTest: the departure date is the last working day and counts as service', function (): void {
    $settlement = app(FinalSettlementBuilder::class)->build(settlementEmployee(departed: '2022-12-31'));

    expect((float) $settlement->gratuity_amount)->toBe(1350.0)
        ->and($settlement->gratuity_breakdown['rows'])->toHaveCount(1)
        ->and($settlement->service_days)->toBe(1096)
        ->and((float) $settlement->final_salary_amount)->toBe(900.0);
});

it('GratuitySettlementTest: a paid or cancelled settlement cannot be sent back into the approval workflow', function (): void {
    $builder = app(FinalSettlementBuilder::class);
    $draft = $builder->build(settlementEmployee());
    $paid = $builder->build(settlementEmployee());
    $paid->markAsPaid();
    $cancelled = $builder->build(settlementEmployee());
    $cancelled->cancel();

    gratuityFlow(User::withoutEvents(fn (): User => User::factory()->create()));

    expect($draft->canBeSubmittedForApproval())->toBeTrue();

    foreach ([$paid->fresh(), $cancelled->fresh()] as $settlement) {
        $status = $settlement->status;

        expect($settlement->canBeSubmittedForApproval())->toBeFalse();

        foreach (['onApprovalSubmitted', 'onApprovalApproved', 'onApprovalRejected'] as $hook) {
            $settlement->$hook(new Approval);
            expect($settlement->fresh()->status)->toBe($status);
        }
    }
});

it('GratuitySettlementTest: cancelling a settlement cancels its pending approval', function (): void {
    $approver = User::withoutEvents(fn (): User => User::factory()->create());
    $flow = gratuityFlow($approver);
    $settlement = app(FinalSettlementBuilder::class)->build(settlementEmployee());
    $approval = $settlement->submitForApproval($flow, $approver->id);

    $settlement->fresh()->cancel();

    expect($settlement->fresh()->status)->toBe(SettlementStatus::Cancelled)
        ->and($approval->fresh()->status)->toBe(ApprovalStatus::Cancelled)
        ->and($settlement->fresh()->isPendingApproval())->toBeFalse();
});

it('GratuitySettlementTest: remembers which loans were offset and clears only those', function (): void {
    $employee = settlementEmployee();
    $offset = settlementLoan($employee, 200);
    $settlement = app(FinalSettlementBuilder::class)->build($employee);

    expect($settlement->gratuity_breakdown['loan_ids'])->toBe([$offset->id]);

    $settlement->markAsPaid();

    expect($offset->fresh()->status)->toBe(LoanStatus::Completed);
});

it('GratuitySettlementTest: refuses to pay when loans changed since the draft was built', function (string $change): void {
    $employee = settlementEmployee();
    $loan = settlementLoan($employee, 200);
    $settlement = app(FinalSettlementBuilder::class)->build($employee);

    $other = match ($change) {
        'new active loan'     => settlementLoan($employee, 100),
        'balance changed'     => tap($loan)->update(['amount_remaining' => 150]),
        'loan no longer live' => tap($loan)->update(['status' => LoanStatus::Completed]),
    };

    expect(fn () => $settlement->markAsPaid())->toThrow(RuntimeException::class, __('payroll::exceptions.settlement_changed'))
        ->and($settlement->fresh()->status)->toBe(SettlementStatus::Draft)
        ->and($settlement->fresh()->paid_at)->toBeNull()
        ->and($other->fresh()->status)->not->toBe(LoanStatus::Cancelled);
})->with(['new active loan', 'balance changed', 'loan no longer live']);

it('GratuitySettlementTest: refuses to pay when the departure month was paid after the draft was built', function (): void {
    $employee = settlementEmployee(departed: '2023-01-16');
    $settlement = app(FinalSettlementBuilder::class)->build($employee);

    expect((float) $settlement->final_salary_amount)->toBeGreaterThan(0.0);

    $payslip = app(PayrollCalculator::class)->calculatePayslip($employee, 2023, 1);
    $payslip->update(['status' => PayslipStatus::Validated]);

    expect(fn () => $settlement->markAsPaid())->toThrow(RuntimeException::class, __('payroll::exceptions.settlement_changed'));
});

it('GratuitySettlementTest: a stale copy cannot pay a settlement that was already paid', function (): void {
    $settlement = app(FinalSettlementBuilder::class)->build(settlementEmployee());
    $stale = EndOfServiceSettlement::query()->findOrFail($settlement->id);

    $settlement->markAsPaid();
    $paidAt = $settlement->fresh()->paid_at;

    expect(fn () => $stale->markAsPaid())->toThrow(RuntimeException::class)
        ->and($settlement->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
});

it('GratuitySettlementTest: the database allows one live settlement per employee', function (): void {
    $employee = settlementEmployee();
    $first = app(FinalSettlementBuilder::class)->build($employee);
    $attributes = collect($first->getAttributes())->except(['id', 'live_employee_id'])->all();

    expect($first->fresh()->live_employee_id)->toBe($employee->id)
        ->and(fn () => EndOfServiceSettlement::query()->create($attributes))->toThrow(QueryException::class);

    $first->fresh()->cancel();

    expect($first->fresh()->live_employee_id)->toBeNull();

    $second = EndOfServiceSettlement::query()->create($attributes);

    expect($second->live_employee_id)->toBe($employee->id);
});
