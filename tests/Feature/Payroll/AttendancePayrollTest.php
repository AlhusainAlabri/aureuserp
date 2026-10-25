<?php

use App\Support\ExtensionPermissionsSync;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\PasiFixtures;
use Tests\Helpers\PayrollWithoutAttendance;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Filament\Pages\ManagePayrollAttendanceSettings;
use Webkul\Payroll\Filament\Resources\PayslipResource;
use Webkul\Payroll\Filament\Resources\PayslipResource\Pages\ViewPayslip;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Services\AttendanceBridge;
use Webkul\Payroll\Services\PayrollCalculator;
use Webkul\Payroll\Settings\PayrollAttendanceSettings;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Calendar;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'payroll'     => 'payroll_salary_components',
        'attendances' => 'attendance_punches',
    ]);
    AttendanceFixtures::useLocalTimezone();
    PayrollWithoutAttendance::restore();
    PasiFixtures::clearRules();
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2000-01-01');
    PasiFixtures::rule(PasiCategory::Expat, 0, 0, null, '2000-01-01');
});

/**
 * Omani employee (PASI 7 %) with basic 840 on the Monday-Friday 08:00-17:00 calendar. October 2026 has 22
 * Monday-Friday days; the holiday on Wednesday 14 October leaves 21 working days of 8 hours, so the hourly
 * rate is 840 / 21 / 8 = 5.000.
 */
function attendancePayrollEmployee(float $basic = 840): Employee
{
    $company = PasiFixtures::company('OM');
    $employee = PasiFixtures::employee($company, 'OM', $basic);
    $calendar = AttendanceFixtures::calendar($company);
    $employee->update(['calendar_id' => $calendar->id]);
    AttendanceFixtures::holiday('2026-10-14', '2026-10-14', $calendar->id);

    return $employee->fresh();
}

/**
 * Two absent days, 90 late minutes, approved overtime 2 h at 1.25 and 1 h at 1.5, plus a pending and a
 * rejected request that must never be paid.
 */
function attendancePayrollMonth(Employee $employee): void
{
    $day = fn (string $date, DayStatus $status, int $late = 0): AttendanceDay => AttendanceDay::query()->create([
        'employee_id'      => $employee->id,
        'date'             => $date,
        'status'           => $status,
        'expected_minutes' => 480,
        'late_minutes'     => $late,
    ]);

    $day('2026-10-01', DayStatus::Absent);
    $day('2026-10-02', DayStatus::Absent);
    $day('2026-10-05', DayStatus::Present, 60);
    $day('2026-10-06', DayStatus::Present, 30);

    foreach ([
        ['2026-10-05', 120, 1.25, OvertimeStatus::Approved],
        ['2026-10-06', 60, 1.5, OvertimeStatus::Approved],
        ['2026-10-07', 60, 1.25, OvertimeStatus::Pending],
        ['2026-10-08', 60, 1.5, OvertimeStatus::Rejected],
    ] as [$date, $minutes, $multiplier, $status]) {
        OvertimeRequest::query()->create([
            'employee_id' => $employee->id,
            'date'        => $date,
            'minutes'     => $minutes,
            'day_type'    => OvertimeDayType::Normal,
            'multiplier'  => $multiplier,
            'status'      => $status,
        ]);
    }
}

function attendancePayrollToggles(bool $absence = false, bool $lateness = false, bool $overtime = false): void
{
    $settings = app(PayrollAttendanceSettings::class);
    $settings->deduct_absence = $absence;
    $settings->deduct_lateness = $lateness;
    $settings->pay_overtime = $overtime;
    $settings->save();
}

function attendancePayslip(Employee $employee, int $year = 2026, int $month = 10): Payslip
{
    return app(PayrollCalculator::class)->calculatePayslip($employee, $year, $month);
}

function attendanceLine(Payslip $payslip, string $code): ?float
{
    $line = $payslip->lines()->where('code', $code)->first();

    return $line === null ? null : (float) $line->amount;
}

it('AttendancePayrollTest: the settings default to every toggle off', function (): void {
    $settings = app(PayrollAttendanceSettings::class);

    expect(PayrollAttendanceSettings::group())->toBe('payroll_attendance')
        ->and($settings->deduct_absence)->toBeFalse()
        ->and($settings->deduct_lateness)->toBeFalse()
        ->and($settings->pay_overtime)->toBeFalse();
});

it('AttendancePayrollTest: the bridge maps the attendance summary to payroll figures', function (): void {
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    $bridge = app(AttendanceBridge::class);

    expect($bridge->available())->toBeTrue()
        ->and($bridge->summary($employee, 2026, 10))->toBe([
            'has_schedule'   => true,
            'working_days'   => 21,
            'absent_days'    => 2,
            'late_minutes'   => 90,
            'daily_hours'    => 8.0,
            'overtime_hours' => ['1.25' => 2.0, '1.50' => 1.0],
        ]);
});

it('AttendancePayrollTest: keeps the legacy 22-day month when the attendance plugin is absent', function (): void {
    PayrollWithoutAttendance::use();
    attendancePayrollToggles(true, true, true);
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    $payslip = attendancePayslip($employee);

    expect((float) $payslip->working_days)->toBe(22.0)
        ->and((float) $payslip->worked_days)->toBe(22.0)
        ->and((float) $payslip->basic_salary)->toBe(840.0)
        ->and($payslip->absent_days)->toBeNull()
        ->and($payslip->late_minutes)->toBeNull()
        ->and($payslip->overtime_hours)->toBeNull()
        ->and(attendanceLine($payslip, 'LATE'))->toBeNull()
        ->and(attendanceLine($payslip, 'OVERTIME'))->toBeNull()
        ->and((float) $payslip->net_amount)->toBe(781.2);
});

it('AttendancePayrollTest: keeps the legacy 22-day month for an employee without any schedule', function (): void {
    attendancePayrollToggles(true, true, true);
    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM', 840);
    Calendar::query()->whereNull('company_id')->update(['is_active' => false]);
    attendancePayrollMonth($employee);

    expect(app(AttendanceBridge::class)->summary($employee, 2026, 10))->toBeNull();

    $payslip = attendancePayslip($employee);

    expect((float) $payslip->working_days)->toBe(22.0)
        ->and((float) $payslip->basic_salary)->toBe(840.0)
        ->and($payslip->absent_days)->toBeNull()
        ->and($payslip->late_minutes)->toBeNull()
        ->and($payslip->overtime_hours)->toBeNull()
        ->and(attendanceLine($payslip, 'LATE'))->toBeNull()
        ->and(attendanceLine($payslip, 'OVERTIME'))->toBeNull();
});

it('AttendancePayrollTest: with every toggle off the calendar sets the working days and nothing is deducted or paid', function (): void {
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    $payslip = attendancePayslip($employee);

    expect((float) $payslip->working_days)->toBe(21.0)
        ->and((float) $payslip->worked_days)->toBe(21.0)
        ->and((float) $payslip->basic_salary)->toBe(840.0)
        ->and((float) $payslip->absent_days)->toBe(2.0)
        ->and($payslip->late_minutes)->toBe(90)
        ->and((float) $payslip->overtime_hours)->toBe(3.0)
        ->and(attendanceLine($payslip, 'LATE'))->toBeNull()
        ->and(attendanceLine($payslip, 'OVERTIME'))->toBeNull()
        ->and((float) $payslip->gross_amount)->toBe(840.0)
        ->and((float) $payslip->net_amount)->toBe(781.2);
});

it('AttendancePayrollTest: installing attendance moves the unpaid-leave proration from 22 days to the calendar', function (): void {
    if (! Schema::hasTable('time_off_leaves') || ! Schema::hasColumn('time_off_leave_types', 'unpaid')) {
        $this->markTestSkipped('Time Off is not installed in the test database.');
    }

    // March 2027: 23 Monday-Friday days on the default calendar the employee falls back to.
    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM', 1000);
    $leaveTypeId = DB::table('time_off_leave_types')->insertGetId([
        'name' => 'Unpaid attendance payroll test', 'unpaid' => true, 'created_at' => now(), 'updated_at' => now(),
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

    PayrollWithoutAttendance::use();
    $legacy = attendancePayslip($employee, 2027, 3);

    expect((float) $legacy->working_days)->toBe(22.0)
        ->and((float) $legacy->basic_salary)->toBe(500.0);

    PayrollWithoutAttendance::restore();
    app(PayrollCalculator::class)->recalculatePayslip($legacy->fresh());
    $calendar = $legacy->fresh();

    expect((float) $calendar->working_days)->toBe(23.0)
        ->and((float) $calendar->worked_days)->toBe(12.0)
        ->and((float) $calendar->basic_salary)->toBe(round(1000 * 12 / 23, 3));
});

it('AttendancePayrollTest: deducting absence alone pro-rates basic by the absent days', function (): void {
    attendancePayrollToggles(absence: true);
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    $payslip = attendancePayslip($employee);

    expect((float) $payslip->working_days)->toBe(21.0)
        ->and((float) $payslip->worked_days)->toBe(19.0)
        ->and((float) $payslip->basic_salary)->toBe(760.0)
        ->and((float) $payslip->absent_days)->toBe(2.0)
        ->and(attendanceLine($payslip, 'LATE'))->toBeNull()
        ->and(attendanceLine($payslip, 'OVERTIME'))->toBeNull()
        ->and(attendanceLine($payslip, 'PASI_EMP'))->toBe(53.2);
});

it('AttendancePayrollTest: an absence under leave approved later is deducted once, through the unpaid leave', function (): void {
    if (! Schema::hasTable('time_off_leaves') || ! Schema::hasColumn('time_off_leave_types', 'unpaid')) {
        $this->markTestSkipped('Time Off is not installed in the test database.');
    }

    attendancePayrollToggles(absence: true);
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    // The 1st and 2nd were closed as absent; unpaid leave for those two days was approved afterwards.
    DB::table('time_off_leaves')->insert([
        'employee_id'       => $employee->id,
        'holiday_status_id' => DB::table('time_off_leave_types')->insertGetId([
            'name' => 'Unpaid late approval '.uniqid(), 'unpaid' => true, 'created_at' => now(), 'updated_at' => now(),
        ]),
        'state'          => 'validate_two',
        'date_from'      => '2026-10-01 00:00:00',
        'date_to'        => '2026-10-02 23:59:59',
        'number_of_days' => 2,
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $payslip = attendancePayslip($employee);

    expect((float) $payslip->unpaid_leave_days)->toBe(2.0)
        ->and((float) $payslip->absent_days)->toBe(0.0)
        ->and((float) $payslip->worked_days)->toBe(19.0)
        ->and((float) $payslip->basic_salary)->toBe(760.0);
});

it('AttendancePayrollTest: an absence on a holiday added later costs nothing', function (): void {
    attendancePayrollToggles(absence: true);
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    // Thursday 1 October was closed as absent, then declared a public holiday: 20 working days remain.
    AttendanceFixtures::holiday('2026-10-01', '2026-10-01', $employee->calendar_id);

    $payslip = attendancePayslip($employee);

    expect((float) $payslip->working_days)->toBe(20.0)
        ->and((float) $payslip->absent_days)->toBe(1.0)
        ->and((float) $payslip->worked_days)->toBe(19.0)
        ->and((float) $payslip->basic_salary)->toBe(round(840 * 19 / 20, 3));
});

it('AttendancePayrollTest: deducting lateness alone adds one LATE deduction at the hourly rate', function (): void {
    attendancePayrollToggles(lateness: true);
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    $payslip = attendancePayslip($employee);
    $line = $payslip->lines()->where('code', 'LATE')->first();

    expect($line)->not->toBeNull()
        ->and($line->component_id)->toBeNull()
        ->and($line->type)->toBe(SalaryComponentType::Deduction)
        ->and((float) $line->amount)->toBe(7.5)
        ->and($line->name)->toBe(__('payroll::payslip.late_deduction'))
        ->and((float) $payslip->basic_salary)->toBe(840.0)
        ->and(attendanceLine($payslip, 'OVERTIME'))->toBeNull()
        ->and((float) $payslip->deductions_amount)->toBe(round(58.8 + 7.5, 3))
        ->and((float) $payslip->net_amount)->toBe(round(840 - 58.8 - 7.5, 3));
});

it('AttendancePayrollTest: paying overtime alone pays approved requests only, each at its own multiplier', function (): void {
    attendancePayrollToggles(overtime: true);
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    $payslip = attendancePayslip($employee);
    $line = $payslip->lines()->where('code', 'OVERTIME')->first();

    // 2 h x 5.000 x 1.25 + 1 h x 5.000 x 1.5; the pending and rejected hours are ignored.
    expect($line)->not->toBeNull()
        ->and($line->component_id)->toBeNull()
        ->and($line->type)->toBe(SalaryComponentType::Earning)
        ->and((float) $line->amount)->toBe(20.0)
        ->and($line->name)->toBe(__('payroll::payslip.overtime_pay'))
        ->and((float) $payslip->basic_salary)->toBe(840.0)
        ->and(attendanceLine($payslip, 'LATE'))->toBeNull()
        ->and((float) $payslip->gross_amount)->toBe(860.0)
        ->and((float) $payslip->pasi_base)->toBe(840.0)
        ->and(attendanceLine($payslip, 'PASI_EMP'))->toBe(58.8)
        ->and(attendanceLine($payslip, 'PASI_EMR'))->toBe(96.6)
        ->and((float) $payslip->net_amount)->toBe(round(860 - 58.8, 3));
});

it('AttendancePayrollTest: all toggles together keep net equal to earnings minus deductions and PASI on the basic only', function (): void {
    attendancePayrollToggles(true, true, true);
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    $payslip = attendancePayslip($employee);

    expect((float) $payslip->basic_salary)->toBe(760.0)
        ->and(attendanceLine($payslip, 'LATE'))->toBe(7.5)
        ->and(attendanceLine($payslip, 'OVERTIME'))->toBe(20.0)
        ->and((float) $payslip->pasi_base)->toBe(760.0)
        ->and(attendanceLine($payslip, 'PASI_EMP'))->toBe(53.2)
        ->and((float) $payslip->gross_amount)->toBe(780.0)
        ->and((float) $payslip->deductions_amount)->toBe(round(53.2 + 7.5, 3))
        ->and((float) $payslip->net_amount)->toBe(round(
            $payslip->getEarningsTotal() - $payslip->getDeductionsTotal(),
            3,
        ))
        ->and((float) $payslip->net_amount)->toBe(719.3);
});

it('AttendancePayrollTest: recalculating twice keeps one LATE and one OVERTIME line and follows the toggles', function (): void {
    attendancePayrollToggles(true, true, true);
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);

    $calculator = app(PayrollCalculator::class);
    $payslip = attendancePayslip($employee);
    $calculator->recalculatePayslip($payslip->fresh());
    $calculator->recalculatePayslip($payslip->fresh());

    expect($payslip->lines()->where('code', 'LATE')->count())->toBe(1)
        ->and($payslip->lines()->where('code', 'OVERTIME')->count())->toBe(1)
        ->and((float) $payslip->fresh()->net_amount)->toBe(719.3);

    attendancePayrollToggles(absence: true);
    $calculator->recalculatePayslip($payslip->fresh());

    expect($payslip->lines()->where('code', 'LATE')->count())->toBe(0)
        ->and($payslip->lines()->where('code', 'OVERTIME')->count())->toBe(0)
        ->and((float) $payslip->fresh()->basic_salary)->toBe(760.0);

    PayrollWithoutAttendance::use();
    $calculator->recalculatePayslip($payslip->fresh());
    $legacy = $payslip->fresh();

    expect((float) $legacy->working_days)->toBe(22.0)
        ->and((float) $legacy->basic_salary)->toBe(840.0)
        ->and($legacy->absent_days)->toBeNull()
        ->and($legacy->late_minutes)->toBeNull()
        ->and($legacy->overtime_hours)->toBeNull();
});

it('AttendancePayrollTest: the payslip view and PDF show the attendance figures', function (): void {
    $employee = attendancePayrollEmployee();
    attendancePayrollMonth($employee);
    $payslip = attendancePayslip($employee);

    $html = view('payroll::payroll.pdf.payslip', [
        'payslip'  => $payslip,
        'employee' => $payslip->employee,
        'company'  => $payslip->batch?->company,
        'lines'    => $payslip->lines,
    ])->render();

    expect($html)->toContain(__('payroll::payroll.pdf.payslip.attendance'))
        ->and($html)->toContain(__('payroll::payroll.fields.working_days'))
        ->and($html)->toContain(__('payroll::payroll.fields.absent_days'))
        ->and($html)->toContain(__('payroll::payroll.fields.late_minutes'))
        ->and($html)->toContain(__('payroll::payroll.fields.overtime_hours'))
        ->and($html)->toContain('21.0')
        ->and($html)->toContain('2.0')
        ->and($html)->toContain('90')
        ->and($html)->toContain('3.00');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    attendancePayrollUser(FilamentShield::getResourcePermissions(PayslipResource::class));

    Livewire::test(ViewPayslip::class, ['record' => $payslip->getRouteKey()])
        ->assertSuccessful()
        ->assertSee(__('payroll::payroll.fields.absent_days'))
        ->assertSee(__('payroll::payroll.fields.late_minutes'))
        ->assertSee(__('payroll::payroll.fields.overtime_hours'))
        ->assertSee('3.00');
});

it('AttendancePayrollTest: the PDF has no attendance section for a legacy payslip', function (): void {
    PayrollWithoutAttendance::use();
    $employee = attendancePayrollEmployee();
    $payslip = attendancePayslip($employee);

    $html = view('payroll::payroll.pdf.payslip', [
        'payslip'  => $payslip,
        'employee' => $payslip->employee,
        'company'  => $payslip->batch?->company,
        'lines'    => $payslip->lines,
    ])->render();

    expect($html)->not->toContain(__('payroll::payroll.fields.late_minutes'))
        ->and($html)->not->toContain(__('payroll::payroll.fields.overtime_hours'));

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    attendancePayrollUser(FilamentShield::getResourcePermissions(PayslipResource::class));

    Livewire::test(ViewPayslip::class, ['record' => $payslip->getRouteKey()])
        ->assertSuccessful()
        ->assertDontSee(__('payroll::payroll.fields.late_minutes'))
        ->assertDontSee(__('payroll::payroll.fields.overtime_hours'));
});

/**
 * @param  array<int|string, string>  $permissions
 */
function attendancePayrollUser(array $permissions): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

it('AttendancePayrollTest: the settings page saves the toggles for a permitted user', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $keys = collect(FilamentShield::getPages()[ManagePayrollAttendanceSettings::class]['permissions'])->keys()->all();

    expect($keys)->toBe(['page_payroll_manage_payroll_attendance_settings']);

    attendancePayrollUser($keys);

    expect(ManagePayrollAttendanceSettings::canAccess())->toBeTrue();

    Livewire::test(ManagePayrollAttendanceSettings::class)
        ->assertSuccessful()
        ->fillForm(['deduct_absence' => true, 'deduct_lateness' => false, 'pay_overtime' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(PayrollAttendanceSettings::class);
    $settings->refresh();

    expect($settings->deduct_absence)->toBeTrue()
        ->and($settings->deduct_lateness)->toBeFalse()
        ->and($settings->pay_overtime)->toBeTrue();
});

it('AttendancePayrollTest: the settings page is forbidden without its permission', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    attendancePayrollUser([]);

    expect(ManagePayrollAttendanceSettings::canAccess())->toBeFalse();

    Livewire::test(ManagePayrollAttendanceSettings::class)->assertForbidden();
});

it('AttendancePayrollTest: syncPayroll grants the settings page to roles that manage salary components', function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $manager = Role::findOrCreate('attendance-payroll-sync-manager', 'web');
    $viewer = Role::findOrCreate('attendance-payroll-sync-viewer', 'web');

    foreach (['update_payroll_salary::component', 'view_any_payroll_salary::component'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $manager->syncPermissions(['update_payroll_salary::component']);
    $viewer->syncPermissions(['view_any_payroll_salary::component']);

    app(ExtensionPermissionsSync::class)->syncPayroll();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($manager->fresh()->hasPermissionTo('page_payroll_manage_payroll_attendance_settings'))->toBeTrue()
        ->and($viewer->fresh()->hasPermissionTo('page_payroll_manage_payroll_attendance_settings'))->toBeFalse();
});
