<?php

use App\Support\Exports\Utf8Csv;
use App\Support\ExtensionPermissionsSync;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Filament\Pages\ImportAttendance;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages\CreateAttendanceDay;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages\EditAttendanceDay;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages\ListAttendanceDays;
use Webkul\Attendance\Filament\Resources\OvertimeRequestResource;
use Webkul\Attendance\Filament\Resources\OvertimeRequestResource\Pages\ListOvertimeRequests;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Policies\AttendanceDayPolicy;
use Webkul\Attendance\Policies\OvertimeRequestPolicy;
use Webkul\Attendance\Services\AttendanceDayCalculator;
use Webkul\Attendance\Services\AttendanceDayCsv;
use Webkul\Attendance\Services\AttendanceScope;
use Webkul\Attendance\Services\AttendanceSummary;
use Webkul\Attendance\Services\ManualDayEntry;
use Webkul\Attendance\Services\OvertimeDecision;
use Webkul\Attendance\Services\PunchRecorder;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $settings = app(AttendanceSettings::class);
    $settings->grace_minutes = 10;
    $settings->minimum_overtime_minutes = 30;
    $settings->overtime_multiplier_normal = 1.25;
    $settings->overtime_multiplier_rest = 1.5;
    $settings->overtime_multiplier_holiday = 1.5;
    $settings->save();

    // 2026-11-10 is a Tuesday; the working month is November 2026.
    $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Muscat'));
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function attendanceKey(string $resource, string $action): string
{
    return FilamentShield::getResourcePolicyActionsWithPermissions($resource)[Str::camel($action)];
}

/**
 * @return list<string>
 */
function attendanceDayKeys(string ...$actions): array
{
    return array_map(fn (string $action): string => attendanceKey(AttendanceDayResource::class, $action), $actions);
}

/**
 * @return list<string>
 */
function attendanceOvertimeKeys(string ...$actions): array
{
    return array_map(fn (string $action): string => attendanceKey(OvertimeRequestResource::class, $action), $actions);
}

/**
 * @param  list<string>  $permissions
 */
function attendanceGrant(User $user, array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function attendanceUser(): User
{
    return User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
}

/**
 * HR (no employee record), a manager with one team member, an unrelated colleague, and one day row each on 2026-11-03.
 *
 * @return array<string, mixed>
 */
function attendanceWorld(): array
{
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company);

    $manager = AttendanceFixtures::employee($company, $calendar);
    $member = AttendanceFixtures::employee($company, $calendar, ['attendance_manager_id' => $manager->user_id]);
    $outsider = AttendanceFixtures::employee($company, $calendar);

    $days = [];

    foreach (['manager' => $manager, 'member' => $member, 'outsider' => $outsider] as $key => $employee) {
        $days[$key] = AttendanceDay::factory()->create([
            'employee_id'      => $employee->id,
            'date'             => '2026-11-03',
            'worked_minutes'   => 480,
            'expected_minutes' => 480,
        ]);
    }

    return [
        'company'  => $company,
        'calendar' => $calendar,
        'hr'       => attendanceUser(),
        'manager'  => $manager,
        'member'   => $member,
        'outsider' => $outsider,
        'days'     => $days,
    ];
}

/**
 * Reads the role's permissions from the database; the permission registrar caches lookups per instance.
 */
function attendanceRoleHas(Role $role, string $permission): bool
{
    return $role->permissions()->where('name', $permission)->exists();
}

function attendanceActingAs(User $user): User
{
    test()->actingAs($user);

    return $user;
}

function attendanceHr(User $hr): User
{
    attendanceGrant($hr, [
        ...attendanceDayKeys('view_any', 'view', 'create', 'update'),
        ...attendanceOvertimeKeys('view_any', 'view', 'create', 'update'),
    ]);

    return attendanceActingAs($hr);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function attendanceRequest(Employee $employee, array $overrides = []): OvertimeRequest
{
    return OvertimeRequest::factory()->create(array_merge([
        'employee_id' => $employee->id,
        'date'        => '2026-11-03',
        'minutes'     => 120,
    ], $overrides));
}

// ---------------------------------------------------------------- scoping

it('AttendanceResourcesTest: HR with the update permission sees every day', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    expect(AttendanceScope::employeeIdsFor($world['hr']))->toBeNull();

    Livewire::test(ListAttendanceDays::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(array_values($world['days']));
});

it('AttendanceResourcesTest: a manager sees the team and self but not other employees', function (): void {
    $world = attendanceWorld();
    $user = attendanceGrant($world['manager']->user, attendanceDayKeys('view_any', 'view'));
    attendanceActingAs($user);

    expect(AttendanceScope::employeeIdsFor($user)->sort()->values()->all())
        ->toBe(collect([$world['manager']->id, $world['member']->id])->sort()->values()->all());

    Livewire::test(ListAttendanceDays::class)
        ->assertCanSeeTableRecords([$world['days']['manager'], $world['days']['member']])
        ->assertCanNotSeeTableRecords([$world['days']['outsider']]);
});

it('AttendanceResourcesTest: a plain permitted viewer sees only their own days', function (): void {
    $world = attendanceWorld();
    $user = attendanceGrant($world['outsider']->user, attendanceDayKeys('view_any', 'view'));
    attendanceActingAs($user);

    expect(AttendanceScope::employeeIdsFor($user)->all())->toBe([$world['outsider']->id]);

    Livewire::test(ListAttendanceDays::class)
        ->assertCanSeeTableRecords([$world['days']['outsider']])
        ->assertCanNotSeeTableRecords([$world['days']['manager'], $world['days']['member']]);
});

it('AttendanceResourcesTest: a user without the view permission is forbidden', function (): void {
    $world = attendanceWorld();
    attendanceActingAs($world['outsider']->user);

    Livewire::test(ListAttendanceDays::class)->assertForbidden();
    Livewire::test(ListOvertimeRequests::class)->assertForbidden();
});

it('AttendanceResourcesTest: a viewer without an employee record sees nothing', function (): void {
    $world = attendanceWorld();
    $user = attendanceGrant(attendanceUser(), attendanceDayKeys('view_any', 'view'));
    attendanceActingAs($user);

    expect(AttendanceScope::employeeIdsFor($user)->all())->toBe([]);

    Livewire::test(ListAttendanceDays::class)
        ->assertCanNotSeeTableRecords(array_values($world['days']));
});

it('AttendanceResourcesTest: the policy checks the record is in scope, not just the permission', function (): void {
    $world = attendanceWorld();
    $user = attendanceGrant($world['manager']->user, attendanceDayKeys('view_any', 'view'));
    $policy = new AttendanceDayPolicy;

    expect($policy->view($user, $world['days']['member']))->toBeTrue()
        ->and($policy->view($user, $world['days']['manager']))->toBeTrue()
        ->and($policy->view($user, $world['days']['outsider']))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $world['days']['outsider']))->toBeFalse()
        ->and($policy->update($user, $world['days']['member']))->toBeFalse();
});

// ---------------------------------------------------------------- tabs and filters

it('AttendanceResourcesTest: the Late, Absent and Overtime tabs narrow the list', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    $late = AttendanceDay::factory()->create(['employee_id' => $world['member']->id, 'date' => '2026-11-04', 'late_minutes' => 15]);
    $absent = AttendanceDay::factory()->create(['employee_id' => $world['member']->id, 'date' => '2026-11-05', 'status' => DayStatus::Absent]);
    $overtime = AttendanceDay::factory()->create(['employee_id' => $world['member']->id, 'date' => '2026-11-06', 'overtime_minutes' => 90]);
    $plain = $world['days']['member'];

    Livewire::test(ListAttendanceDays::class)
        ->assertCanSeeTableRecords([$late, $absent, $overtime, $plain])
        ->set('activeTab', 'late')
        ->assertCanSeeTableRecords([$late])
        ->assertCanNotSeeTableRecords([$absent, $overtime, $plain])
        ->set('activeTab', 'absent')
        ->assertCanSeeTableRecords([$absent])
        ->assertCanNotSeeTableRecords([$late, $overtime, $plain])
        ->set('activeTab', 'overtime')
        ->assertCanSeeTableRecords([$overtime])
        ->assertCanNotSeeTableRecords([$late, $absent, $plain])
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$late, $absent, $overtime, $plain]);
});

it('AttendanceResourcesTest: the month filter defaults to this month and the employee filter narrows the list', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    $october = AttendanceDay::factory()->create(['employee_id' => $world['member']->id, 'date' => '2026-10-20']);

    Livewire::test(ListAttendanceDays::class)
        ->assertCanSeeTableRecords(array_values($world['days']))
        ->assertCanNotSeeTableRecords([$october])
        ->filterTable('month', ['month' => '2026-10'])
        ->assertCanSeeTableRecords([$october])
        ->assertCanNotSeeTableRecords(array_values($world['days']))
        ->filterTable('month', ['month' => '2026-11'])
        ->filterTable('employee_id', $world['member']->id)
        ->assertCanSeeTableRecords([$world['days']['member']])
        ->assertCanNotSeeTableRecords([$world['days']['manager'], $world['days']['outsider']]);
});

// ---------------------------------------------------------------- manual entry

it('AttendanceResourcesTest: a manual entry is stored as an override with UTC times and queues the overtime request', function (): void {
    $world = attendanceWorld();
    $hr = attendanceHr($world['hr']);

    Livewire::test(CreateAttendanceDay::class)
        ->fillForm([
            'employee_id'         => $world['member']->id,
            'date'                => '2026-11-04',
            'status'              => DayStatus::Present->value,
            'first_in'            => '2026-11-04 08:00:00',
            'last_out'            => '2026-11-04 19:00:00',
            'late_minutes'        => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes'    => 120,
            'override_note'       => 'Device was offline',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $day = AttendanceDay::query()->where('employee_id', $world['member']->id)->whereDate('date', '2026-11-04')->firstOrFail();

    expect($day->is_manual_override)->toBeTrue()
        ->and($day->edited_by)->toBe($hr->id)
        ->and($day->override_note)->toBe('Device was offline')
        ->and($day->status)->toBe(DayStatus::Present)
        ->and($day->first_in->format('Y-m-d H:i'))->toBe('2026-11-04 04:00')
        ->and($day->last_out->format('Y-m-d H:i'))->toBe('2026-11-04 15:00')
        ->and($day->worked_minutes)->toBe(660)
        ->and($day->expected_minutes)->toBe(480)
        ->and($day->overtime_minutes)->toBe(120)
        ->and(AttendancePunch::query()->where('employee_id', $world['member']->id)->count())->toBe(0);

    $request = OvertimeRequest::query()->where('employee_id', $world['member']->id)->firstOrFail();

    expect($request->status)->toBe(OvertimeStatus::Pending)
        ->and($request->minutes)->toBe(120)
        ->and($request->day_type)->toBe(OvertimeDayType::Normal)
        ->and((float) $request->multiplier)->toBe(1.25);
});

it('AttendanceResourcesTest: a manual entry converts the times with the employee time zone', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    $riyadh = AttendanceFixtures::employee($world['company'], $world['calendar'], ['time_zone' => 'Asia/Riyadh']);

    Livewire::test(CreateAttendanceDay::class)
        ->fillForm([
            'employee_id'   => $riyadh->id,
            'date'          => '2026-11-04',
            'status'        => DayStatus::Present->value,
            'first_in'      => '2026-11-04 08:00:00',
            'last_out'      => '2026-11-04 17:00:00',
            'override_note' => 'Zone check',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $day = AttendanceDay::query()->where('employee_id', $riyadh->id)->firstOrFail();

    expect($day->first_in->format('H:i'))->toBe('05:00')
        ->and($day->last_out->format('H:i'))->toBe('14:00');

    Livewire::test(EditAttendanceDay::class, ['record' => $day->id])
        ->assertFormSet(['first_in' => '2026-11-04 08:00', 'last_out' => '2026-11-04 17:00']);
});

it('AttendanceResourcesTest: the note is required', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    Livewire::test(CreateAttendanceDay::class)
        ->fillForm([
            'employee_id' => $world['member']->id,
            'date'        => '2026-11-04',
            'status'      => DayStatus::Present->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['override_note' => 'required']);

    expect(AttendanceDay::query()->whereDate('date', '2026-11-04')->count())->toBe(0);
});

it('AttendanceResourcesTest: a second row for the same employee and date shows an error on the date', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    Livewire::test(CreateAttendanceDay::class)
        ->fillForm([
            'employee_id'   => $world['member']->id,
            'date'          => '2026-11-03',
            'status'        => DayStatus::Present->value,
            'override_note' => 'Again',
        ])
        ->call('create')
        ->assertHasFormErrors(['date']);

    expect(AttendanceDay::query()->where('employee_id', $world['member']->id)->count())->toBe(1);
});

it('AttendanceResourcesTest: validates the minutes and the order of the times', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    Livewire::test(CreateAttendanceDay::class)
        ->fillForm([
            'employee_id'      => $world['member']->id,
            'date'             => '2026-11-04',
            'status'           => DayStatus::Present->value,
            'first_in'         => '2026-11-04 17:00:00',
            'last_out'         => '2026-11-04 08:00:00',
            'late_minutes'     => -5,
            'overtime_minutes' => -1,
            'override_note'    => 'Bad numbers',
        ])
        ->call('create')
        ->assertHasFormErrors(['last_out', 'late_minutes', 'overtime_minutes']);
});

it('AttendanceResourcesTest: editing a computed row makes it an override, keeps punches and survives a later punch', function (): void {
    $world = attendanceWorld();
    $hr = attendanceHr($world['hr']);
    $member = $world['member'];

    AttendanceFixtures::punch($member, '2026-11-04 08:30:00');
    AttendanceFixtures::punch($member, '2026-11-04 17:00:00');
    $computed = app(AttendanceDayCalculator::class)->calculate($member, Carbon::parse('2026-11-04', 'Asia/Muscat'));

    $computed = $computed->fresh();

    expect($computed->is_manual_override)->toBeFalse()
        ->and($computed->late_minutes)->toBe(20);

    Livewire::test(EditAttendanceDay::class, ['record' => $computed->id])
        ->fillForm(['late_minutes' => 0, 'override_note' => 'Arrived on time, clock was wrong'])
        ->call('save')
        ->assertHasNoFormErrors();

    $day = $computed->fresh();

    expect($day->is_manual_override)->toBeTrue()
        ->and($day->edited_by)->toBe($hr->id)
        ->and($day->late_minutes)->toBe(0)
        ->and($day->override_note)->toBe('Arrived on time, clock was wrong')
        ->and(AttendancePunch::query()->where('employee_id', $member->id)->count())->toBe(2);

    app(PunchRecorder::class)->record($member, Carbon::parse('2026-11-04 18:30:00', 'Asia/Muscat'), PunchSource::Manual);

    $after = $day->fresh();

    expect($after->late_minutes)->toBe(0)
        ->and($after->overtime_minutes)->toBe(0)
        ->and($after->last_out->format('H:i'))->toBe($day->last_out->format('H:i'))
        ->and(AttendancePunch::query()->where('employee_id', $member->id)->count())->toBe(3);
});

it('AttendanceResourcesTest: editing the overtime follows a pending request and leaves a decided one alone', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    $day = $world['days']['member'];
    $request = attendanceRequest($world['member'], ['minutes' => 60]);
    $day->update(['overtime_minutes' => 60]);

    Livewire::test(EditAttendanceDay::class, ['record' => $day->id])
        ->fillForm(['overtime_minutes' => 150, 'override_note' => 'Longer evening'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($request->fresh()->minutes)->toBe(150);

    $request->update(['status' => OvertimeStatus::Approved]);

    Livewire::test(EditAttendanceDay::class, ['record' => $day->id])
        ->fillForm(['overtime_minutes' => 45, 'override_note' => 'Trim it'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($request->fresh()->minutes)->toBe(150)
        ->and($request->fresh()->status)->toBe(OvertimeStatus::Approved);
});

it('AttendanceResourcesTest: creating and editing need the update permission and no row can be deleted', function (): void {
    $world = attendanceWorld();
    $viewer = attendanceGrant($world['manager']->user, attendanceDayKeys('view_any', 'view'));
    attendanceActingAs($viewer);

    Livewire::test(CreateAttendanceDay::class)->assertForbidden();
    Livewire::test(EditAttendanceDay::class, ['record' => $world['days']['manager']->id])->assertForbidden();

    expect(method_exists(AttendanceDayPolicy::class, 'delete'))->toBeFalse()
        ->and(method_exists(AttendanceDayPolicy::class, 'deleteAny'))->toBeFalse()
        ->and(method_exists(OvertimeRequestPolicy::class, 'delete'))->toBeFalse();
});

it('AttendanceResourcesTest: the manual form only offers and accepts employees in scope', function (): void {
    $world = attendanceWorld();

    // Update permission on the day resource means "all employees"; without it the service refuses outsiders.
    $user = attendanceGrant($world['manager']->user, attendanceDayKeys('view_any', 'view', 'create'));

    expect(fn () => app(ManualDayEntry::class)->create([
        'employee_id'   => $world['outsider']->id,
        'date'          => '2026-11-04',
        'status'        => DayStatus::Present->value,
        'override_note' => 'Not mine',
    ], $user))->toThrow(ValidationException::class);

    expect(AttendanceDay::query()->whereDate('date', '2026-11-04')->count())->toBe(0);
});

// ---------------------------------------------------------------- CSV

it('AttendanceResourcesTest: the CSV has a BOM, a header and one row per day with local times', function (): void {
    $world = attendanceWorld();
    $member = AttendanceFixtures::employee($world['company'], $world['calendar'], ['name' => 'Salim, Al Busaidi', 'time_zone' => 'Asia/Riyadh']);

    $day = AttendanceDay::factory()->create([
        'employee_id'         => $member->id,
        'date'                => '2026-11-04',
        'first_in'            => Carbon::parse('2026-11-04 05:10:00', 'UTC'),
        'last_out'            => Carbon::parse('2026-11-04 14:00:00', 'UTC'),
        'worked_minutes'      => 530,
        'late_minutes'        => 10,
        'early_leave_minutes' => 0,
        'overtime_minutes'    => 0,
        'status'              => DayStatus::Present,
    ]);

    $csv = app(AttendanceDayCsv::class)->build(collect([$day->load('employee')]));
    $lines = array_values(array_filter(preg_split('/\r?\n/', substr($csv, strlen(Utf8Csv::BOM)))));

    expect(str_starts_with($csv, Utf8Csv::BOM))->toBeTrue()
        ->and($lines)->toHaveCount(2)
        ->and($lines[0])->toContain(__('attendances::attendances.day.columns.employee'))
        ->and($lines[1])->toContain('"Salim, Al Busaidi"')
        ->and($lines[1])->toContain('2026-11-04')
        ->and($lines[1])->toContain('08:10')
        ->and($lines[1])->toContain('17:00')
        ->and($lines[1])->toContain('8:50')
        ->and($lines[1])->toContain(DayStatus::Present->getLabel());
});

it('AttendanceResourcesTest: the CSV neutralises cells that a spreadsheet would run as formulas', function (): void {
    $world = attendanceWorld();
    $member = AttendanceFixtures::employee($world['company'], $world['calendar'], ['name' => '=HYPERLINK("http://evil.example")']);
    $day = AttendanceDay::factory()->create(['employee_id' => $member->id, 'date' => '2026-11-04']);

    $csv = app(AttendanceDayCsv::class)->build(collect([$day->load('employee')]));

    expect($csv)->toContain("'=HYPERLINK");
});

it('AttendanceResourcesTest: the export action downloads the visible month as attendance-days-YYYY-MM.csv', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    Livewire::test(ListAttendanceDays::class)
        ->callAction('exportCsv')
        ->assertFileDownloaded('attendance-days-2026-11.csv');

    Livewire::test(ListAttendanceDays::class)
        ->filterTable('month', ['month' => '2026-10'])
        ->callAction('exportCsv')
        ->assertFileDownloaded('attendance-days-2026-10.csv');
});

it('AttendanceResourcesTest: the export only contains the rows the user may see', function (): void {
    $world = attendanceWorld();
    $user = attendanceGrant($world['outsider']->user, attendanceDayKeys('view_any', 'view'));
    attendanceActingAs($user);

    $expected = app(AttendanceDayCsv::class)->build(collect([$world['days']['outsider']->load('employee')]));

    Livewire::test(ListAttendanceDays::class)
        ->callAction('exportCsv')
        ->assertFileDownloaded('attendance-days-2026-11.csv', $expected);
});

// ---------------------------------------------------------------- overtime decisions

it('AttendanceResourcesTest: the overtime list defaults to Pending and has Approved, Rejected and All tabs', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    $pending = attendanceRequest($world['member']);
    $approved = attendanceRequest($world['member'], ['date' => '2026-11-04', 'status' => OvertimeStatus::Approved]);
    $rejected = attendanceRequest($world['member'], ['date' => '2026-11-05', 'status' => OvertimeStatus::Rejected]);

    Livewire::test(ListOvertimeRequests::class)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$approved, $rejected])
        ->set('activeTab', 'approved')
        ->assertCanSeeTableRecords([$approved])
        ->assertCanNotSeeTableRecords([$pending, $rejected])
        ->set('activeTab', 'rejected')
        ->assertCanSeeTableRecords([$rejected])
        ->assertCanNotSeeTableRecords([$pending, $approved])
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$pending, $approved, $rejected]);
});

it('AttendanceResourcesTest: the overtime list is scoped like the day list', function (): void {
    $world = attendanceWorld();
    $mine = attendanceRequest($world['manager']);
    $team = attendanceRequest($world['member']);
    $other = attendanceRequest($world['outsider']);

    $user = attendanceGrant($world['manager']->user, attendanceOvertimeKeys('view_any', 'view'));
    attendanceActingAs($user);

    Livewire::test(ListOvertimeRequests::class)
        ->assertCanSeeTableRecords([$mine, $team])
        ->assertCanNotSeeTableRecords([$other]);
});

it('AttendanceResourcesTest: approving stores the decision and makes the request count in the summary', function (): void {
    $world = attendanceWorld();
    $hr = attendanceHr($world['hr']);
    $request = attendanceRequest($world['member'], ['minutes' => 120]);

    expect(app(AttendanceSummary::class)->forMonth($world['member'], 2026, 11)->overtimeHours)->toBe([]);

    Livewire::test(ListOvertimeRequests::class)
        ->callTableAction('approve', $request, ['note' => 'Project deadline'])
        ->assertNotified();

    $request->refresh();

    expect($request->status)->toBe(OvertimeStatus::Approved)
        ->and($request->decided_by)->toBe($hr->id)
        ->and($request->decided_at)->not->toBeNull()
        ->and($request->note)->toBe('Project deadline')
        ->and(app(AttendanceSummary::class)->forMonth($world['member'], 2026, 11)->overtimeHours)->toBe(['1.25' => 2.0]);
});

it('AttendanceResourcesTest: rejecting stores the note and keeps the request out of the summary', function (): void {
    $world = attendanceWorld();
    $hr = attendanceHr($world['hr']);
    $request = attendanceRequest($world['member']);

    Livewire::test(ListOvertimeRequests::class)
        ->callTableAction('reject', $request, ['note' => 'Not authorised'])
        ->assertNotified();

    $request->refresh();

    expect($request->status)->toBe(OvertimeStatus::Rejected)
        ->and($request->decided_by)->toBe($hr->id)
        ->and($request->decided_at)->not->toBeNull()
        ->and($request->note)->toBe('Not authorised')
        ->and(app(AttendanceSummary::class)->forMonth($world['member'], 2026, 11)->overtimeHours)->toBe([]);
});

it('AttendanceResourcesTest: the note is optional', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);
    $request = attendanceRequest($world['member']);

    Livewire::test(ListOvertimeRequests::class)
        ->callTableAction('approve', $request)
        ->assertHasNoTableActionErrors();

    expect($request->fresh()->status)->toBe(OvertimeStatus::Approved)
        ->and($request->fresh()->note)->toBeNull();
});

it('AttendanceResourcesTest: bulk approve decides every selected pending request', function (): void {
    $world = attendanceWorld();
    $hr = attendanceHr($world['hr']);
    $first = attendanceRequest($world['member']);
    $second = attendanceRequest($world['outsider']);

    Livewire::test(ListOvertimeRequests::class)
        ->callTableBulkAction('approve', [$first, $second])
        ->assertNotified();

    expect($first->fresh()->status)->toBe(OvertimeStatus::Approved)
        ->and($second->fresh()->status)->toBe(OvertimeStatus::Approved)
        ->and($first->fresh()->decided_by)->toBe($hr->id)
        ->and($second->fresh()->decided_at)->not->toBeNull();
});

it('AttendanceResourcesTest: a decided request cannot be decided again and shows no actions', function (): void {
    $world = attendanceWorld();
    $hr = attendanceHr($world['hr']);
    $approved = attendanceRequest($world['member'], ['status' => OvertimeStatus::Approved, 'decided_by' => $hr->id, 'decided_at' => now()]);

    Livewire::test(ListOvertimeRequests::class)
        ->set('activeTab', 'approved')
        ->assertTableActionHidden('approve', $approved)
        ->assertTableActionHidden('reject', $approved);

    $policy = new OvertimeRequestPolicy;

    expect($policy->approve($hr, $approved))->toBeFalse()
        ->and($policy->reject($hr, $approved))->toBeFalse()
        ->and($policy->update($hr, $approved))->toBeFalse();

    expect(fn () => app(OvertimeDecision::class)->approve($approved, $hr))->toThrow(AuthorizationException::class)
        ->and($approved->fresh()->decided_at->toDateTimeString())->toBe($approved->decided_at->toDateTimeString());
});

it('AttendanceResourcesTest: a second decision on the same request is refused after the first', function (): void {
    $world = attendanceWorld();
    $hr = attendanceHr($world['hr']);
    $request = attendanceRequest($world['member']);
    $stale = OvertimeRequest::query()->findOrFail($request->id);

    app(OvertimeDecision::class)->approve($request, $hr);

    expect(fn () => app(OvertimeDecision::class)->reject($stale, $hr, 'late change of mind'))
        ->toThrow(AttendanceException::class, __('attendances::attendances.overtime.errors.already_decided'));

    expect($request->fresh()->status)->toBe(OvertimeStatus::Approved)
        ->and($request->fresh()->note)->toBeNull();
});

it('AttendanceResourcesTest: nobody can approve their own request', function (): void {
    $world = attendanceWorld();
    $user = attendanceGrant($world['manager']->user, [
        ...attendanceOvertimeKeys('view_any', 'view', 'update'),
        ...attendanceDayKeys('view_any', 'view', 'update'),
    ]);
    attendanceActingAs($user);
    $own = attendanceRequest($world['manager']);
    $team = attendanceRequest($world['member']);

    Livewire::test(ListOvertimeRequests::class)
        ->callTableAction('approve', $own)
        ->assertNotified(__('attendances::attendances.overtime.errors.own_request'));

    expect($own->fresh()->status)->toBe(OvertimeStatus::Pending)
        ->and($own->fresh()->decided_by)->toBeNull();

    Livewire::test(ListOvertimeRequests::class)
        ->callTableBulkAction('approve', [$own, $team])
        ->assertNotified();

    expect($own->fresh()->status)->toBe(OvertimeStatus::Pending)
        ->and($team->fresh()->status)->toBe(OvertimeStatus::Approved);

    expect(fn () => app(OvertimeDecision::class)->reject($own, $user))
        ->toThrow(AttendanceException::class, __('attendances::attendances.overtime.errors.own_request'));
});

it('AttendanceResourcesTest: a manager decides for the team only when granted the permission, and only for in-scope employees', function (): void {
    $world = attendanceWorld();
    $team = attendanceRequest($world['member']);
    $other = attendanceRequest($world['outsider']);

    $viewer = attendanceGrant($world['manager']->user, attendanceOvertimeKeys('view_any', 'view'));
    attendanceActingAs($viewer);

    Livewire::test(ListOvertimeRequests::class)->assertTableActionHidden('approve', $team);

    expect(fn () => app(OvertimeDecision::class)->approve($team, $viewer))->toThrow(AuthorizationException::class);

    attendanceGrant($viewer, attendanceOvertimeKeys('update'));

    Livewire::test(ListOvertimeRequests::class)
        ->assertTableActionVisible('approve', $team)
        ->callTableAction('approve', $team);

    expect($team->fresh()->status)->toBe(OvertimeStatus::Approved)
        ->and(fn () => app(OvertimeDecision::class)->approve($other, $viewer))->toThrow(AuthorizationException::class)
        ->and($other->fresh()->status)->toBe(OvertimeStatus::Pending)
        ->and((new OvertimeRequestPolicy)->approve($viewer, $other))->toBeFalse()
        ->and((new OvertimeRequestPolicy)->view($viewer, $other))->toBeFalse();
});

// ---------------------------------------------------------------- permissions

it('AttendanceResourcesTest: policy permission keys are the ones Shield generates, with no delete', function (): void {
    foreach ([AttendanceDayResource::class, OvertimeRequestResource::class] as $resource) {
        $actions = FilamentShield::getResourcePolicyActionsWithPermissions($resource);

        expect(array_keys($actions))->toBe(['viewAny', 'view', 'create', 'update']);
    }

    expect(attendanceDayKeys('view_any', 'view', 'create', 'update'))->toBe([
        'view_any_attendance_attendance::day',
        'view_attendance_attendance::day',
        'create_attendance_attendance::day',
        'update_attendance_attendance::day',
    ])->and(attendanceOvertimeKeys('view_any', 'view', 'create', 'update'))->toBe([
        'view_any_attendance_overtime::request',
        'view_attendance_overtime::request',
        'create_attendance_overtime::request',
        'update_attendance_overtime::request',
    ]);

    $user = attendanceUser();
    attendanceGrant($user, [...attendanceDayKeys('view_any', 'view', 'create', 'update')]);
    $day = AttendanceDay::factory()->create(['employee_id' => AttendanceFixtures::employee(AttendanceFixtures::company(), null)->id]);
    $policy = new AttendanceDayPolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $day))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $day))->toBeTrue()
        ->and(array_keys(FilamentShield::getPages()[ImportAttendance::class]['permissions']))->toBe(['page_attendance_import_attendance']);
});

it('AttendanceResourcesTest: the scope and policies use the exact Shield permission names', function (): void {
    expect(AttendanceScope::ALL_EMPLOYEES_PERMISSION)->toBe(attendanceKey(AttendanceDayResource::class, 'update'));

    $source = file_get_contents(base_path('plugins/webkul/attendances/src/Policies/AttendanceDayPolicy.php'))
        .file_get_contents(base_path('plugins/webkul/attendances/src/Policies/OvertimeRequestPolicy.php'));

    foreach ([...attendanceDayKeys('view_any', 'view', 'create', 'update'), ...attendanceOvertimeKeys('view_any', 'view', 'create', 'update')] as $key) {
        expect($source)->toContain("'".$key."'");
    }
});

it('AttendanceResourcesTest: the screens render in Arabic and every new string exists in both languages', function (): void {
    $world = attendanceWorld();
    attendanceHr($world['hr']);

    $keys = [
        'attendances::attendances.day.columns.employee',
        'attendances::attendances.day.columns.override_note',
        'attendances::attendances.day.tabs.late',
        'attendances::attendances.day.actions.export_csv',
        'attendances::attendances.day.errors.duplicate',
        'attendances::attendances.overtime.columns.hours',
        'attendances::attendances.overtime.actions.approve',
        'attendances::attendances.overtime.notifications.bulk_approved',
        'attendances::attendances.overtime.errors.already_decided',
        'attendances::attendances.overtime.errors.own_request',
    ];

    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);

        foreach ($keys as $key) {
            expect(__($key))->not->toBe($key);
        }
    }

    app()->setLocale('ar');

    Livewire::test(ListAttendanceDays::class)
        ->assertSee(__('attendances::attendances.day.tabs.absent'))
        ->assertSee(__('attendances::attendances.day.columns.worked'));
    Livewire::test(ListOvertimeRequests::class)->assertSuccessful();

    app()->setLocale('en');
});

it('AttendanceResourcesTest: the plugin shield config lists both resources', function (): void {
    $manage = config('filament-shield.resources.manage');

    expect($manage)->toHaveKeys([AttendanceDayResource::class, OvertimeRequestResource::class]);
});

it('AttendanceResourcesTest: syncAttendance grants read to team roles and write to HR roles, twice, without widening', function (): void {
    $names = ['manager', 'department_manager', 'hr_manager', 'Admin', 'super_admin', 'attendance-sync-other'];
    $roles = collect($names)->mapWithKeys(fn (string $name): array => [$name => Role::findOrCreate($name, 'web')]);

    Permission::findOrCreate('keep_me_attendance_sync', 'web');
    $roles['manager']->givePermissionTo('keep_me_attendance_sync');

    app(ExtensionPermissionsSync::class)->syncAttendance();
    app(ExtensionPermissionsSync::class)->syncAttendance();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $read = [
        ...attendanceDayKeys('view_any', 'view'),
        ...attendanceOvertimeKeys('view_any', 'view'),
    ];
    $write = [
        ...attendanceDayKeys('create', 'update'),
        ...attendanceOvertimeKeys('create', 'update'),
        'page_attendance_import_attendance',
    ];

    foreach (['manager', 'department_manager', 'hr_manager', 'Admin', 'super_admin'] as $name) {
        foreach ($read as $permission) {
            expect(attendanceRoleHas($roles[$name], $permission))->toBeTrue("$name read $permission");
        }
    }

    foreach (['hr_manager', 'Admin', 'super_admin'] as $name) {
        foreach ($write as $permission) {
            expect(attendanceRoleHas($roles[$name], $permission))->toBeTrue("$name write $permission");
        }
    }

    foreach (['manager', 'department_manager'] as $name) {
        foreach ($write as $permission) {
            expect(attendanceRoleHas($roles[$name], $permission))->toBeFalse("$name must not have $permission");
        }
    }

    foreach ([...$read, ...$write] as $permission) {
        expect(attendanceRoleHas($roles['attendance-sync-other'], $permission))->toBeFalse();
    }

    expect(attendanceRoleHas($roles['manager'], 'keep_me_attendance_sync'))->toBeTrue()
        ->and($roles['hr_manager']->fresh()->permissions()->whereIn('name', [...$read, ...$write])->count())->toBe(count($read) + count($write))
        ->and($roles['manager']->fresh()->permissions()->whereIn('name', $read)->count())->toBe(count($read));
});

it('AttendanceResourcesTest: syncAll includes the attendance step', function (): void {
    $role = Role::findOrCreate('hr_manager', 'web');

    app(ExtensionPermissionsSync::class)->syncAll();

    expect(attendanceRoleHas($role, attendanceKey(AttendanceDayResource::class, 'update')))->toBeTrue();
});
