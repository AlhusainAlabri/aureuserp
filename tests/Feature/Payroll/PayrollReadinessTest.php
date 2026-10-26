<?php

use App\Models\Hr\EmployeeContract;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\PasiFixtures;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Services\AttendanceSummary;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Services\PayrollReadinessChecker;
use Webkul\Payroll\Services\SalaryStructureAssigner;
use Webkul\Security\Models\User;

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
