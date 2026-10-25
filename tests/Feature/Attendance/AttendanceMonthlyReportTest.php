<?php

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Filament\Pages\MonthlyAttendanceSummary;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Services\AttendanceMonthlyReport;
use Webkul\Attendance\Services\AttendanceScope;
use Webkul\Attendance\Services\AttendanceSummary;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Calendar;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // November 2026 is the working month (21 Monday-Friday days).
    $this->travelTo(Carbon\Carbon::parse('2026-11-10 10:00:00', 'Asia/Muscat'));
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function monthlyReportPageKey(): string
{
    return array_key_first(FilamentShield::getPages()[MonthlyAttendanceSummary::class]['permissions']);
}

/**
 * @param  list<string>  $permissions
 */
function monthlyReportUser(array $permissions, ?User $user = null): User
{
    $user ??= User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

/**
 * Update on the day resource widens the scope to every employee (HR).
 */
function monthlyReportHr(): User
{
    return monthlyReportUser([monthlyReportPageKey(), AttendanceScope::ALL_EMPLOYEES_PERMISSION]);
}

/**
 * A manager with one team member, an outsider, and attendance data for the three in November 2026.
 *
 * @return array<string, Employee>
 */
function monthlyReportWorld(): array
{
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company);

    $manager = AttendanceFixtures::employee($company, $calendar, ['name' => 'Report Manager']);
    $member = AttendanceFixtures::employee($company, $calendar, ['name' => 'Report Member', 'attendance_manager_id' => $manager->user_id]);
    $outsider = AttendanceFixtures::employee($company, $calendar, ['name' => 'Report Outsider']);

    foreach ([$manager, $member, $outsider] as $employee) {
        AttendanceDay::query()->create(['employee_id' => $employee->id, 'date' => '2026-11-03', 'status' => DayStatus::Absent, 'expected_minutes' => 480]);
    }

    AttendanceDay::query()->create(['employee_id' => $member->id, 'date' => '2026-11-04', 'status' => DayStatus::Present, 'expected_minutes' => 480, 'late_minutes' => 25]);

    foreach ([['2026-11-05', 90, 1.25], ['2026-11-06', 60, 1.5]] as [$date, $minutes, $multiplier]) {
        OvertimeRequest::query()->create([
            'employee_id' => $member->id,
            'date'        => $date,
            'minutes'     => $minutes,
            'day_type'    => OvertimeDayType::Normal,
            'multiplier'  => $multiplier,
            'status'      => OvertimeStatus::Approved,
        ]);
    }

    return compact('company', 'calendar', 'manager', 'member', 'outsider');
}

/**
 * @return array<string, array<string, mixed>> report rows keyed by employee name
 */
function monthlyReportRows(User $user, int $year = 2026, int $month = 11): array
{
    return collect(app(AttendanceMonthlyReport::class)->build($user, $year, $month)['rows'])->keyBy('employee')->all();
}

it('AttendanceMonthlyReportTest: each row equals the AttendanceSummary of that employee', function (): void {
    $world = monthlyReportWorld();
    $hr = monthlyReportHr();
    $rows = monthlyReportRows($hr);

    foreach (['manager', 'member', 'outsider'] as $key) {
        $summary = app(AttendanceSummary::class)->forMonth($world[$key], 2026, 11);
        $row = $rows[$world[$key]->name];

        expect($row['working_days'])->toBe($summary->workingDays)
            ->and($row['absent_days'])->toBe($summary->absentDays)
            ->and($row['late_minutes'])->toBe($summary->lateMinutes)
            ->and($row['overtime_hours'])->toBe(round(array_sum($summary->overtimeHours), 2))
            ->and($row['overtime_by_multiplier'])->toBe($summary->overtimeHours);
    }

    expect($rows['Report Member'])->toMatchArray(['working_days' => 21, 'absent_days' => 1, 'late_minutes' => 25, 'overtime_hours' => 2.5])
        ->and($rows['Report Member']['overtime_by_multiplier'])->toBe(['1.25' => 1.5, '1.50' => 1.0]);
});

it('AttendanceMonthlyReportTest: the totals add up the rows with a schedule', function (): void {
    $world = monthlyReportWorld();
    // The manager sees themself and the team member (the test database holds other employees too).
    $report = app(AttendanceMonthlyReport::class)->build(monthlyReportUser([monthlyReportPageKey()], $world['manager']->user), 2026, 11);

    expect($report['totals'])->toMatchArray([
        'employees'      => 2,
        'working_days'   => 42,
        'absent_days'    => 2,
        'late_minutes'   => 25,
        'overtime_hours' => 2.5,
    ])->and($report['totals']['overtime_by_multiplier'])->toBe(['1.25' => 1.5, '1.50' => 1.0]);
});

it('AttendanceMonthlyReportTest: a manager sees the team and self, a plain viewer only self, a stranger nothing', function (): void {
    $world = monthlyReportWorld();

    $manager = monthlyReportUser([monthlyReportPageKey()], $world['manager']->user);
    expect(array_keys(monthlyReportRows($manager)))->toEqualCanonicalizing(['Report Manager', 'Report Member']);

    $member = monthlyReportUser([monthlyReportPageKey()], $world['member']->user);
    expect(array_keys(monthlyReportRows($member)))->toBe(['Report Member']);

    $stranger = monthlyReportUser([monthlyReportPageKey()]);
    expect(monthlyReportRows($stranger))->toBe([]);

    monthlyReportUser([monthlyReportPageKey()], $world['manager']->user);
    Livewire::test(MonthlyAttendanceSummary::class)
        ->assertSee('Report Member')
        ->assertDontSee('Report Outsider');
});

it('AttendanceMonthlyReportTest: employees without a schedule show dashes and stay out of the totals', function (): void {
    $world = monthlyReportWorld();
    $company = $world['company'];

    Calendar::query()->update(['is_active' => false]);
    $unscheduled = AttendanceFixtures::employee($company, null, ['name' => 'Report Unscheduled', 'attendance_manager_id' => $world['manager']->user_id]);
    AttendanceDay::query()->create(['employee_id' => $unscheduled->id, 'date' => '2026-11-03', 'status' => DayStatus::Absent, 'late_minutes' => 99]);

    // The scheduled employees keep their own calendar; only the unscheduled one has none.
    $manager = monthlyReportUser([monthlyReportPageKey()], $world['manager']->user);
    $report = app(AttendanceMonthlyReport::class)->build($manager, 2026, 11);
    $row = collect($report['rows'])->firstWhere('employee', 'Report Unscheduled');

    expect($row)->toMatchArray([
        'has_schedule'   => false,
        'working_days'   => null,
        'absent_days'    => null,
        'late_minutes'   => null,
        'overtime_hours' => null,
    ])->and($report['totals']['employees'])->toBe(2)
        ->and($report['totals']['absent_days'])->toBe(2)
        ->and($report['totals']['late_minutes'])->toBe(25);

    Livewire::test(MonthlyAttendanceSummary::class)
        ->assertSee('Report Unscheduled')
        ->assertSee('—');
});

it('AttendanceMonthlyReportTest: inactive employees appear only when they have days in the month', function (): void {
    $world = monthlyReportWorld();
    $company = $world['company'];

    $quietLeaver = AttendanceFixtures::employee($company, $world['calendar'], ['name' => 'Report Quiet Leaver', 'is_active' => false]);
    $busyLeaver = AttendanceFixtures::employee($company, $world['calendar'], ['name' => 'Report Busy Leaver', 'is_active' => false]);
    AttendanceDay::query()->create(['employee_id' => $busyLeaver->id, 'date' => '2026-11-03', 'status' => DayStatus::Present]);
    $archived = AttendanceFixtures::employee($company, $world['calendar'], ['name' => 'Report Archived']);
    AttendanceDay::query()->create(['employee_id' => $archived->id, 'date' => '2026-11-03', 'status' => DayStatus::Present]);
    $archived->delete();
    $quietArchived = AttendanceFixtures::employee($company, $world['calendar'], ['name' => 'Report Quiet Archived']);
    $quietArchived->delete();
    AttendanceDay::query()->create(['employee_id' => $quietLeaver->id, 'date' => '2026-10-31', 'status' => DayStatus::Present]);

    $names = array_keys(monthlyReportRows(monthlyReportHr()));

    expect($names)->toContain('Report Busy Leaver', 'Report Archived')
        ->and($names)->not->toContain('Report Quiet Leaver', 'Report Quiet Archived');
});

it('AttendanceMonthlyReportTest: rows are sorted by name and capped with a notice instead of silently truncated', function (): void {
    $world = monthlyReportWorld();
    $manager = monthlyReportUser([monthlyReportPageKey()], $world['manager']->user);

    $full = app(AttendanceMonthlyReport::class)->build($manager, 2026, 11);
    expect($full['truncated'])->toBeFalse()
        ->and(array_column($full['rows'], 'employee'))->toBe(['Report Manager', 'Report Member']);

    app()->instance(AttendanceMonthlyReport::class, new AttendanceMonthlyReport(app(AttendanceSummary::class), limit: 1));

    $capped = app(AttendanceMonthlyReport::class)->build($manager, 2026, 11);
    expect($capped['truncated'])->toBeTrue()
        ->and(array_column($capped['rows'], 'employee'))->toBe(['Report Manager'])
        ->and($capped['totalEmployees'])->toBe(2);

    Livewire::test(MonthlyAttendanceSummary::class)
        ->assertSee(__('attendances::attendances.monthly.capped', ['shown' => 1, 'total' => 2]));

    expect(AttendanceMonthlyReport::MAX_ROWS)->toBe(500);
});

it('AttendanceMonthlyReportTest: the page defaults to the current month and the selectors change it', function (): void {
    monthlyReportWorld();
    monthlyReportHr();

    $page = Livewire::test(MonthlyAttendanceSummary::class)
        ->assertSet('data.year', 2026)
        ->assertSet('data.month', 11)
        ->assertSee('Report Member');

    expect($page->instance()->rows->filter(fn (array $row): bool => str_starts_with($row['employee'], 'Report'))->count())->toBe(3);

    $page->set('data.month', 10);
    expect($page->instance()->rows->firstWhere('employee', 'Report Member')['absent_days'])->toBe(0);

    $page->set('data.month', 99)->assertSuccessful();
    expect($page->instance()->rows->firstWhere('employee', 'Report Member')['working_days'])->toBe(21);
});

it('AttendanceMonthlyReportTest: the export downloads attendance-summary-YYYY-MM.csv with a header and one line per row', function (): void {
    $world = monthlyReportWorld();
    $manager = monthlyReportUser([monthlyReportPageKey()], $world['manager']->user);

    $report = app(AttendanceMonthlyReport::class);
    $csv = $report->csv($report->build($manager, 2026, 11)['rows']);

    expect($csv)->toStartWith("\xEF\xBB\xBF");

    $lines = explode("\n", trim(substr($csv, 3)));

    expect($lines)->toHaveCount(3)
        ->and($lines[0])->toBe('Employee,"Working days","Absent days","Late (min)","Approved overtime (h)"')
        ->and($lines[1])->toBe('"Report Manager",21,1,0,0.00')
        ->and($lines[2])->toBe('"Report Member",21,1,25,2.50');

    Livewire::test(MonthlyAttendanceSummary::class)
        ->callAction('export')
        ->assertFileDownloaded('attendance-summary-2026-11.csv', $csv);

    Livewire::test(MonthlyAttendanceSummary::class)
        ->set('data.month', 3)
        ->set('data.year', 2026)
        ->callAction('export')
        ->assertFileDownloaded('attendance-summary-2026-03.csv');
});

it('AttendanceMonthlyReportTest: the export only contains the rows the user may see, with empty cells for unscheduled employees', function (): void {
    $world = monthlyReportWorld();
    $unscheduled = AttendanceFixtures::employee($world['company'], null, ['name' => 'Report Nocal', 'calendar_id' => null]);
    Calendar::query()->update(['is_active' => false]);
    $world['manager']->update(['calendar_id' => $world['calendar']->id]);

    $report = app(AttendanceMonthlyReport::class);

    $csv = $report->csv($report->build(monthlyReportHr(), 2026, 11)['rows']);
    expect($csv)->toContain('"Report Nocal",,,,');

    $manager = monthlyReportUser([monthlyReportPageKey()], $world['manager']->user);
    $own = $report->csv($report->build($manager, 2026, 11)['rows']);

    expect($own)->toContain('Report Member')->not->toContain('Report Outsider')->not->toContain('Report Nocal');
});

it('AttendanceMonthlyReportTest: the CSV neutralises names that a spreadsheet would run as formulas', function (): void {
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company);

    foreach (['=HYPERLINK("x")', '+1', '-1', '@sum', "\tTab"] as $name) {
        AttendanceFixtures::employee($company, $calendar, ['name' => $name]);
    }

    $report = app(AttendanceMonthlyReport::class);
    $csv = $report->csv($report->build(monthlyReportHr(), 2026, 11)['rows']);

    foreach (['"\'=HYPERLINK(""x"")"', '\'+1', '\'-1', '\'@sum', "\"'\tTab\""] as $expected) {
        expect($csv)->toContain($expected);
    }
});

it('AttendanceMonthlyReportTest: the page is forbidden without its permission and the key is the one Shield generates', function (): void {
    monthlyReportWorld();

    expect(array_keys(FilamentShield::getPages()[MonthlyAttendanceSummary::class]['permissions']))->toBe(['page_attendance_monthly_attendance_summary']);

    monthlyReportUser([]);
    expect(MonthlyAttendanceSummary::canAccess())->toBeFalse();
    Livewire::test(MonthlyAttendanceSummary::class)->assertForbidden();

    monthlyReportUser([monthlyReportPageKey()]);
    expect(MonthlyAttendanceSummary::canAccess())->toBeTrue()
        ->and(MonthlyAttendanceSummary::getSlug())->toBe('attendances/monthly-summary');
});

it('AttendanceMonthlyReportTest: the permission to see everyone is the day resource update key', function (): void {
    expect(AttendanceScope::ALL_EMPLOYEES_PERMISSION)->toBe(FilamentShield::getResourcePolicyActionsWithPermissions(AttendanceDayResource::class)['update']);
});

it('AttendanceMonthlyReportTest: the page renders in Arabic and every string exists in both languages', function (): void {
    monthlyReportWorld();
    monthlyReportHr();

    $keys = [
        'navigation', 'title', 'export', 'empty', 'totals', 'no_schedule', 'capped', 'employees_counted',
        'columns.employee', 'columns.working_days', 'columns.absent_days', 'columns.late_minutes', 'columns.overtime_hours',
    ];

    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);

        foreach ($keys as $key) {
            expect(__('attendances::attendances.monthly.'.$key))->not->toBe('attendances::attendances.monthly.'.$key);
        }
    }

    app()->setLocale('ar');

    Livewire::test(MonthlyAttendanceSummary::class)
        ->assertSee(__('attendances::attendances.monthly.columns.working_days'))
        ->assertSee(__('attendances::attendances.monthly.totals'));

    app()->setLocale('en');
});
