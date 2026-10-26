<?php

use App\Models\Hr\EmployeeContract;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\PasiFixtures;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Services\AttendanceSummary;
use Webkul\Payroll\Enums\BatchStatus;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Payroll\Services\PayrollAccountingService;
use Webkul\Payroll\Services\PayrollCalculator;
use Webkul\Payroll\Services\PayrollReadinessChecker;
use Webkul\Payroll\Services\SalaryStructureAssigner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Currency;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'payroll'     => 'payroll_salary_components',
        'attendances' => 'attendance_punches',
    ]);
    AttendanceFixtures::useLocalTimezone();
});

function contractFor($employee, float $wage = 600.0): EmployeeContract
{
    return EmployeeContract::query()->create([
        'employee_id'        => $employee->id,
        'contract_type'      => 'fixed_term',
        'start_date'         => '2025-01-01',
        'end_date'           => '2030-12-31',
        'first_joining_date' => '2025-01-01',
        'wage'               => $wage,
        'is_active'          => true,
        'creator_id'         => User::factory()->create()->id,
    ]);
}

it('assigns basic salary and PASI from the active contract and is idempotent', function (): void {
    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM', withPasi: false);
    EmployeeComponent::query()->where('employee_id', $employee->id)->delete();
    contractFor($employee, 600);

    $assigner = app(SalaryStructureAssigner::class);

    expect($assigner->assignFromContract($employee))->toBe(['BASIC', 'PASI_EMP', 'PASI_EMR']);

    $basic = EmployeeComponent::query()
        ->where('employee_id', $employee->id)
        ->whereHas('component', fn ($q) => $q->where('code', 'BASIC'))
        ->first();

    expect((float) $basic->amount)->toBe(600.0)
        ->and($basic->start_date->toDateString())->toBe('2025-01-01')
        ->and($assigner->assignFromContract($employee))->toBe([]);
});

it('assigns nothing without an active contract wage', function (): void {
    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM', withPasi: false);
    EmployeeComponent::query()->where('employee_id', $employee->id)->delete();

    expect(app(SalaryStructureAssigner::class)->assignFromContract($employee))->toBe([]);
});

it('flags employees without basic salary, PASI or closed attendance', function (): void {
    $company = PasiFixtures::company('OM');
    $bare = PasiFixtures::employee($company, 'OM', withPasi: false);
    EmployeeComponent::query()->where('employee_id', $bare->id)->delete();
    $bare->update(['name' => 'Bare Employee']);

    $batch = PayrollBatch::query()->create([
        'company_id'   => $company->id,
        'period_year'  => 2026,
        'period_month' => 10,
        'pay_date'     => '2026-10-25',
    ]);

    $checker = app(PayrollReadinessChecker::class);
    $findings = $checker->check($batch);

    expect($findings['no_basic'])->toContain('Bare Employee')
        ->and($findings['no_pasi'])->toContain('Bare Employee')
        ->and($checker->messages($findings))->not->toBeEmpty();
});

it('counts working days the attendance close has not stored yet', function (): void {
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company);
    $employee = AttendanceFixtures::employee($company, $calendar);

    $summary = app(AttendanceSummary::class);

    expect($summary->openDays($employee, 2026, 10))->toBe(22);

    AttendanceDay::query()->create([
        'employee_id'      => $employee->id,
        'date'             => '2026-10-01',
        'status'           => DayStatus::Present,
        'expected_minutes' => 480,
    ]);

    expect($summary->openDays($employee, 2026, 10))->toBe(21);
});

it('moves the seeded default calendar weekend to Friday and Saturday', function (): void {
    $calendarId = DB::table('calendars')->insertGetId([
        'name'                     => 'Standard 40 hours/week',
        'full_time_required_hours' => 40,
        'hours_per_day'            => 8,
        'timezone'                 => 'UTC',
        'is_active'                => true,
        'created_at'               => now(),
        'updated_at'               => now(),
    ]);

    foreach (['Monday', 'Friday'] as $day) {
        DB::table('calendar_attendances')->insert([
            'calendar_id' => $calendarId, 'name' => "{$day} Morning", 'day_of_week' => strtolower($day),
            'day_period'  => 'morning', 'hour_from' => 8, 'hour_to' => 12, 'duration_days' => 0.5, 'sort' => 10,
            'created_at'  => now(), 'updated_at' => now(),
        ]);
    }

    (require base_path('database/migrations/2026_10_29_100000_shift_default_calendar_weekend_to_friday_saturday.php'))->up();

    expect(DB::table('calendar_attendances')->where('calendar_id', $calendarId)->orderBy('id')->pluck('day_of_week')->all())
        ->toBe(['monday', 'sunday'])
        ->and(DB::table('calendar_attendances')->where('calendar_id', $calendarId)->where('day_of_week', 'sunday')->value('name'))
        ->toBe('Sunday Morning');
});

it('refuses to post a batch without a journal, a journal account or component accounts', function (): void {
    $company = PasiFixtures::company('OM');
    $employee = PasiFixtures::employee($company, 'OM', 600, withPasi: false);

    $batch = PayrollBatch::query()->create([
        'company_id'   => $company->id,
        'period_year'  => 2026,
        'period_month' => 10,
        'pay_date'     => '2026-10-25',
    ]);
    app(PayrollCalculator::class)->generateForBatch($batch);

    $service = app(PayrollAccountingService::class);

    expect(fn () => $service->createDraftJournalEntry($batch->fresh()))
        ->toThrow(RuntimeException::class, __('payroll::exceptions.posting_no_journal'));

    $journal = Journal::factory()->create(['default_account_id' => null]);
    $batch->update(['journal_id' => $journal->id]);

    expect(fn () => $service->createDraftJournalEntry($batch->fresh()))
        ->toThrow(RuntimeException::class, __('payroll::exceptions.posting_journal_no_account'));

    $journal->update(['default_account_id' => Account::factory()->create()->id]);

    expect(fn () => $service->createDraftJournalEntry($batch->fresh()))
        ->toThrow(RuntimeException::class, 'BASIC')
        ->and(Move::query()->where('reference', $batch->reference_number)->exists())->toBeFalse();
});

it('only offers approval submission while the batch is a draft', function (): void {
    $batch = PayrollBatch::query()->create([
        'company_id'   => PasiFixtures::company('OM')->id,
        'period_year'  => 2026,
        'period_month' => 9,
        'pay_date'     => '2026-09-25',
        'status'       => BatchStatus::Paid,
    ]);

    expect($batch->canBeSubmittedForApproval())->toBeFalse();
});

it('posts a balanced entry crediting the employer PASI share to its liability account', function (): void {
    PasiFixtures::clearRules();
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2000-01-01');

    $this->actingAs(User::factory()->create());

    $company = PasiFixtures::company('OM');
    $employee = PasiFixtures::employee($company, 'OM', 1000);
    $company->update(['currency_id' => Currency::factory()->create()->id]);

    $batch = PayrollBatch::query()->create([
        'company_id'   => $company->id,
        'period_year'  => 2026,
        'period_month' => 10,
        'pay_date'     => '2026-10-25',
    ]);
    app(PayrollCalculator::class)->generateForBatch($batch);

    $account = fn (): int => Account::factory()->create()->id;
    $journal = Journal::factory()->create(['default_account_id' => $account(), 'company_id' => $company->id]);
    $batch->update(['journal_id' => $journal->id]);

    foreach (['BASIC', 'PASI_EMP', 'PASI_EMR'] as $code) {
        SalaryComponent::query()->where('code', $code)->update(['account_id' => $account()]);
    }

    $service = app(PayrollAccountingService::class);

    expect(fn () => $service->createDraftJournalEntry($batch->fresh()))
        ->toThrow(RuntimeException::class, 'PASI_EMR');

    $liability = $account();
    SalaryComponent::query()->where('code', 'PASI_EMR')->update(['liability_account_id' => $liability]);

    $move = $service->createDraftJournalEntry($batch->fresh());
    $lines = MoveLine::query()->where('move_id', $move->id)->get();

    expect(round((float) $lines->sum('debit'), 3))->toBe(round((float) $lines->sum('credit'), 3))
        ->and((float) $lines->where('account_id', $liability)->sum('credit'))->toBe(115.0)
        ->and((float) $lines->sum('debit'))->toBeGreaterThan(0);
});

it('posts lateness lines through the system component of the same code', function (): void {
    $this->actingAs(User::factory()->create());

    $company = PasiFixtures::company('OM');
    $company->update(['currency_id' => Currency::factory()->create()->id]);
    $employee = PasiFixtures::employee($company, 'OM', 1000, withPasi: false);

    $batch = PayrollBatch::query()->create([
        'company_id'   => $company->id,
        'period_year'  => 2026,
        'period_month' => 10,
        'pay_date'     => '2026-10-25',
    ]);
    app(PayrollCalculator::class)->generateForBatch($batch);

    $payslip = $batch->payslips()->firstOrFail();
    $payslip->lines()->create([
        'component_id' => null,
        'type'         => 'deduction',
        'code'         => 'LATE',
        'name'         => 'Lateness deduction',
        'quantity'     => 1,
        'rate'         => 0,
        'amount'       => 10,
        'sort_order'   => 920,
    ]);

    $account = fn (): int => Account::factory()->create()->id;
    $batch->update(['journal_id' => Journal::factory()->create(['default_account_id' => $account(), 'company_id' => $company->id])->id]);
    SalaryComponent::query()->where('code', 'BASIC')->update(['account_id' => $account()]);

    $service = app(PayrollAccountingService::class);

    expect(fn () => $service->createDraftJournalEntry($batch->fresh()))->toThrow(RuntimeException::class, 'LATE');

    SalaryComponent::query()->firstOrCreate(['code' => 'LATE'], [
        'name' => 'Lateness deduction', 'name_ar' => 'خصم التأخير', 'type' => 'deduction', 'calculation_type' => 'fixed', 'is_active' => false,
    ])->update(['account_id' => $account()]);

    expect($service->createDraftJournalEntry($batch->fresh())->id)->toBeInt();
});
