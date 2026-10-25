<?php

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Filament\Pages\MyAttendance;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Services\SelfCheckInService;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $settings = app(AttendanceSettings::class);
    $settings->grace_minutes = 10;
    $settings->allow_self_checkin = true;
    $settings->allowed_ip_ranges = [];
    $settings->geofence = null;
    $settings->save();

    // 2026-11-02 is a Monday; the shift is 08:00-17:00.
    $this->travelTo(Carbon::parse('2026-11-02 08:00:00', 'Asia/Muscat'));
});

function selfCheckInEmployee(): Employee
{
    $company = AttendanceFixtures::company();

    return AttendanceFixtures::employee($company, AttendanceFixtures::calendar($company));
}

/**
 * @param  array<string, mixed>  $values
 */
function selfCheckInSettings(array $values): void
{
    $settings = app(AttendanceSettings::class);

    foreach ($values as $key => $value) {
        $settings->{$key} = $value;
    }

    $settings->save();
}

function selfCheckInPunches(Employee $employee): int
{
    return AttendancePunch::query()->where('employee_id', $employee->id)->count();
}

function selfCheckInRefusal(Employee $employee, ?string $ip = null, ?float $latitude = null, ?float $longitude = null): string
{
    try {
        app(SelfCheckInService::class)->punch($employee, $ip, $latitude, $longitude);
    } catch (AttendanceException $exception) {
        return $exception->getMessage();
    }

    return '';
}

it('AttendanceSelfCheckInTest: alternates in and out through the day and stores ip and coordinates', function (): void {
    $employee = selfCheckInEmployee();
    $service = app(SelfCheckInService::class);

    $first = $service->punch($employee, '10.0.0.5', 23.5880, 58.3829);
    expect($first->direction)->toBe(PunchDirection::In)
        ->and($first->source)->toBe(PunchSource::Self)
        ->and($first->ip)->toBe('10.0.0.5')
        ->and((float) $first->latitude)->toBe(23.588)
        ->and((float) $first->longitude)->toBe(58.3829)
        ->and($first->punched_at->format('Y-m-d H:i:s'))->toBe('2026-11-02 04:00:00');

    $this->travelTo(Carbon::parse('2026-11-02 12:00:00', 'Asia/Muscat'));
    expect($service->punch($employee, null, null, null)->direction)->toBe(PunchDirection::Out);

    $this->travelTo(Carbon::parse('2026-11-02 13:00:00', 'Asia/Muscat'));
    expect($service->punch($employee, null, null, null)->direction)->toBe(PunchDirection::In);

    // A new local day starts with "in" again, whatever the last punch of yesterday was.
    $this->travelTo(Carbon::parse('2026-11-03 08:00:00', 'Asia/Muscat'));
    expect($service->punch($employee, null, null, null)->direction)->toBe(PunchDirection::In);
});

it('AttendanceSelfCheckInTest: falls back to counting punches when the last one has no direction', function (): void {
    $employee = selfCheckInEmployee();
    AttendanceFixtures::punch($employee, '2026-11-02 07:00:00', PunchSource::Device);

    expect(app(SelfCheckInService::class)->punch($employee, null, null, null)->direction)->toBe(PunchDirection::Out);
});

it('AttendanceSelfCheckInTest: reads today in the employee time zone', function (): void {
    $employee = selfCheckInEmployee();
    $employee->update(['time_zone' => 'Asia/Tokyo']);

    // 16:00 UTC on 1 November is 01:00 on 2 November in Tokyo (today) but 20:00 on 1 November in Muscat.
    AttendancePunch::query()->create([
        'employee_id' => $employee->id,
        'punched_at'  => '2026-11-01 16:00:00',
        'source'      => PunchSource::Device,
        'direction'   => PunchDirection::In,
    ]);

    expect(app(SelfCheckInService::class)->punch($employee->refresh(), null, null, null)->direction)->toBe(PunchDirection::Out);

    $employee->update(['time_zone' => null]);
    $this->travelTo(now()->addMinutes(5));
    AttendancePunch::query()->where('source', PunchSource::Self)->where('employee_id', $employee->id)->delete();

    expect(app(SelfCheckInService::class)->punch($employee->refresh(), null, null, null)->direction)->toBe(PunchDirection::In);
});

it('AttendanceSelfCheckInTest: refuses when self check-in is switched off', function (): void {
    $employee = selfCheckInEmployee();
    selfCheckInSettings(['allow_self_checkin' => false]);

    expect(selfCheckInRefusal($employee))->toBe(__('attendances::attendances.self_check_in.disabled'))
        ->and(selfCheckInPunches($employee))->toBe(0);
});

it('AttendanceSelfCheckInTest: allows only addresses inside the configured ranges', function (): void {
    $employee = selfCheckInEmployee();
    selfCheckInSettings(['allowed_ip_ranges' => ['192.168.1.0/24', '203.0.113.7']]);

    $message = __('attendances::attendances.self_check_in.ip_not_allowed');

    expect(selfCheckInRefusal($employee, '10.1.1.1'))->toBe($message)
        ->and(selfCheckInRefusal($employee, null))->toBe($message)
        ->and(selfCheckInRefusal($employee, ''))->toBe($message)
        ->and(selfCheckInRefusal($employee, '203.0.113.8'))->toBe($message)
        ->and(selfCheckInPunches($employee))->toBe(0);

    expect(app(SelfCheckInService::class)->punch($employee, '192.168.1.200', null, null))->toBeInstanceOf(AttendancePunch::class);

    $this->travelTo(now()->addMinutes(5));
    expect(app(SelfCheckInService::class)->punch($employee, '203.0.113.7', null, null))->toBeInstanceOf(AttendancePunch::class)
        ->and(selfCheckInPunches($employee))->toBe(2);
});

it('AttendanceSelfCheckInTest: applies the geofence by haversine distance', function (): void {
    $employee = selfCheckInEmployee();
    selfCheckInSettings(['geofence' => ['latitude' => 23.5880, 'longitude' => 58.3829, 'radius_metres' => 200]]);

    $message = __('attendances::attendances.self_check_in.outside_geofence');
    $missing = __('attendances::attendances.self_check_in.location_required');

    // 0.01 degrees of latitude is about 1,112 m: outside.
    expect(selfCheckInRefusal($employee, null, 23.5980, 58.3829))->toBe($message)
        ->and(selfCheckInRefusal($employee, null, null, null))->toBe($missing)
        ->and(selfCheckInRefusal($employee, null, 23.5880, null))->toBe($missing)
        ->and(selfCheckInRefusal($employee, null, null, 58.3829))->toBe($missing)
        ->and(selfCheckInPunches($employee))->toBe(0);

    // 0.001 degrees of latitude is about 111 m: inside; the exact centre is inside too.
    $punch = app(SelfCheckInService::class)->punch($employee, null, 23.5890, 58.3829);
    expect($punch)->toBeInstanceOf(AttendancePunch::class);

    $this->travelTo(now()->addMinutes(5));
    expect(app(SelfCheckInService::class)->punch($employee, null, 23.5880, 58.3829))->toBeInstanceOf(AttendancePunch::class);
});

it('AttendanceSelfCheckInTest: accepts a punch exactly on the geofence radius', function (): void {
    $employee = selfCheckInEmployee();
    selfCheckInSettings(['geofence' => ['latitude' => 0.0, 'longitude' => 0.0, 'radius_metres' => 1000]]);

    // 1,000 m due north is 1000 / 6371000 radians of latitude.
    $latitude = rad2deg(1000 / 6371000);

    expect(app(SelfCheckInService::class)->punch($employee, null, $latitude, 0.0))->toBeInstanceOf(AttendancePunch::class);
});

it('AttendanceSelfCheckInTest: refuses a second punch within 60 seconds of any punch', function (): void {
    $employee = selfCheckInEmployee();
    $service = app(SelfCheckInService::class);
    $message = __('attendances::attendances.self_check_in.too_soon');

    $service->punch($employee, null, null, null);
    expect(selfCheckInPunches($employee))->toBe(1);

    $this->travelTo(now()->addSeconds(59));
    expect(selfCheckInRefusal($employee))->toBe($message)
        ->and(selfCheckInPunches($employee))->toBe(1);

    $this->travelTo(now()->addSeconds(2));
    expect($service->punch($employee, null, null, null)->direction)->toBe(PunchDirection::Out);

    // A device punch counts for the guard too.
    $this->travelTo(Carbon::parse('2026-11-03 09:00:00', 'Asia/Muscat'));
    AttendanceFixtures::punch($employee, '2026-11-03 08:59:30', PunchSource::Device);
    expect(selfCheckInRefusal($employee))->toBe($message);
});

it('AttendanceSelfCheckInTest: a self punch recalculates the day with the late minutes', function (): void {
    $employee = selfCheckInEmployee();
    $this->travelTo(Carbon::parse('2026-11-02 08:25:00', 'Asia/Muscat'));

    app(SelfCheckInService::class)->punch($employee, null, null, null);

    $day = AttendanceDay::query()->where('employee_id', $employee->id)->firstOrFail();
    expect($day->date->toDateString())->toBe('2026-11-02')
        ->and($day->status)->toBe(DayStatus::Present)
        ->and($day->late_minutes)->toBe(15);
});

it('AttendanceSelfCheckInTest: the page lists the employee punches and offers check in then check out', function (): void {
    $employee = selfCheckInEmployee();
    $other = selfCheckInEmployee();
    AttendanceFixtures::punch($other, '2026-11-02 06:00:00');
    $this->actingAs($employee->user);

    $page = Livewire::test(MyAttendance::class)
        ->assertSuccessful()
        ->assertSee(__('attendances::attendances.my_attendance.check_in'))
        ->assertDontSee(__('attendances::attendances.my_attendance.check_out'))
        ->assertSee(__('attendances::attendances.my_attendance.no_punches_today'));

    $page->call('checkInOrOut')->assertNotified(__('attendances::attendances.my_attendance.recorded_in'));

    expect(AttendancePunch::query()->where('employee_id', $employee->id)->count())->toBe(1);
    expect(AttendancePunch::query()->where('employee_id', $employee->id)->first()->ip)->not->toBeNull();

    $page->assertSee('08:00')
        ->assertSee(__('attendances::attendances.my_attendance.check_out'))
        ->assertSee(DayStatus::Present->getLabel());

    $this->travelTo(now()->addMinutes(2));
    $page->call('checkInOrOut')->assertNotified(__('attendances::attendances.my_attendance.recorded_out'));

    expect(AttendancePunch::query()->where('employee_id', $employee->id)->orderBy('id')->pluck('direction')->all())
        ->toBe([PunchDirection::In, PunchDirection::Out]);
});

it('AttendanceSelfCheckInTest: the page shows a danger notification on refusal and records nothing', function (): void {
    $employee = selfCheckInEmployee();
    selfCheckInSettings(['allow_self_checkin' => false]);
    $this->actingAs($employee->user);

    Livewire::test(MyAttendance::class)
        ->call('checkInOrOut')
        ->assertNotified(__('attendances::attendances.self_check_in.disabled'));

    expect(selfCheckInPunches($employee))->toBe(0);
});

it('AttendanceSelfCheckInTest: the page passes the browser location to the geofence check', function (): void {
    $employee = selfCheckInEmployee();
    selfCheckInSettings(['geofence' => ['latitude' => 23.5880, 'longitude' => 58.3829, 'radius_metres' => 200]]);
    $this->actingAs($employee->user);

    $page = Livewire::test(MyAttendance::class)
        ->call('checkInOrOut')
        ->assertNotified(__('attendances::attendances.self_check_in.location_required'));
    expect(selfCheckInPunches($employee))->toBe(0);

    $page->call('checkInOrOut', 23.6980, 58.3829)
        ->assertNotified(__('attendances::attendances.self_check_in.outside_geofence'));
    expect(selfCheckInPunches($employee))->toBe(0);

    $page->call('checkInOrOut', 23.5881, 58.3829)
        ->assertNotified(__('attendances::attendances.my_attendance.recorded_in'));
    expect(selfCheckInPunches($employee))->toBe(1);
});

it('AttendanceSelfCheckInTest: the page shows the month days of the signed-in employee only', function (): void {
    $employee = selfCheckInEmployee();
    $other = selfCheckInEmployee();

    AttendanceDay::query()->create([
        'employee_id'  => $employee->id, 'date' => '2026-11-01', 'worked_minutes' => 480, 'expected_minutes' => 480,
        'late_minutes' => 37, 'status' => DayStatus::Present,
    ]);
    AttendanceDay::query()->create([
        'employee_id'  => $employee->id, 'date' => '2026-10-31', 'worked_minutes' => 0, 'expected_minutes' => 480,
        'late_minutes' => 0, 'status' => DayStatus::Absent,
    ]);
    AttendanceDay::query()->create([
        'employee_id'  => $other->id, 'date' => '2026-11-01', 'worked_minutes' => 0, 'expected_minutes' => 480,
        'late_minutes' => 91, 'status' => DayStatus::Holiday,
    ]);
    $this->actingAs($employee->user);

    $page = Livewire::test(MyAttendance::class)->assertSee('2026-11-01');

    expect($page->instance()->monthDays->pluck('employee_id')->unique()->all())->toBe([$employee->id])
        ->and($page->instance()->monthDays->map(fn (AttendanceDay $day): string => $day->date->toDateString())->all())->toBe(['2026-11-01']);
});

it('AttendanceSelfCheckInTest: a user without an employee record has no entry and no access', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $this->actingAs($user);

    expect(MyAttendance::canAccess())->toBeFalse()
        ->and(MyAttendance::shouldRegisterNavigation())->toBeFalse();

    Livewire::test(MyAttendance::class)->assertForbidden();

    $employee = selfCheckInEmployee();
    $this->actingAs($employee->user);

    expect(MyAttendance::canAccess())->toBeTrue()
        ->and(MyAttendance::shouldRegisterNavigation())->toBeTrue()
        ->and(MyAttendance::getSlug())->toBe('attendances/my-attendance');
});
