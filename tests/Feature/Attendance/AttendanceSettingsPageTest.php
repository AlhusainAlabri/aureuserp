<?php

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Filament\Pages\ManageAttendanceSettings;
use Webkul\Attendance\Rules\IpAddressOrCidr;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $settings = app(AttendanceSettings::class);
    $settings->grace_minutes = 10;
    $settings->overtime_multiplier_normal = 1.25;
    $settings->overtime_multiplier_rest = 1.5;
    $settings->overtime_multiplier_holiday = 1.5;
    $settings->minimum_overtime_minutes = 30;
    $settings->allow_self_checkin = true;
    $settings->allowed_ip_ranges = [];
    $settings->geofence = null;
    $settings->workday_close_time = '23:30';
    $settings->save();
});

function attendanceSettingsUser(bool $permitted = true): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    if ($permitted) {
        $key = array_key_first(FilamentShield::getPages()[ManageAttendanceSettings::class]['permissions']);
        Permission::findOrCreate($key, 'web');
        $user->givePermissionTo($key);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

/**
 * A valid form state, with the given changes.
 *
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function attendanceSettingsForm(array $changes = []): array
{
    return array_merge([
        'grace_minutes'               => 10,
        'overtime_multiplier_normal'  => 1.25,
        'overtime_multiplier_rest'    => 1.5,
        'overtime_multiplier_holiday' => 1.5,
        'minimum_overtime_minutes'    => 30,
        'allow_self_checkin'          => true,
        'allowed_ip_ranges'           => [],
        'geofence_enabled'            => false,
        'workday_close_time'          => '23:30',
    ], $changes);
}

it('AttendanceSettingsPageTest: saving changes every setting', function (): void {
    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm([
            'grace_minutes'               => 15,
            'overtime_multiplier_normal'  => 1.3,
            'overtime_multiplier_rest'    => 2,
            'overtime_multiplier_holiday' => 2.5,
            'minimum_overtime_minutes'    => 45,
            'allow_self_checkin'          => false,
            'allowed_ip_ranges'           => ['203.0.113.7', '10.0.0.0/24', '2001:db8::/32'],
            'workday_close_time'          => '22:15',
        ]))
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(AttendanceSettings::class);

    expect($settings->grace_minutes)->toBe(15)
        ->and($settings->overtime_multiplier_normal)->toBe(1.3)
        ->and($settings->overtime_multiplier_rest)->toBe(2.0)
        ->and($settings->overtime_multiplier_holiday)->toBe(2.5)
        ->and($settings->minimum_overtime_minutes)->toBe(45)
        ->and($settings->allow_self_checkin)->toBeFalse()
        ->and($settings->allowed_ip_ranges)->toBe(['203.0.113.7', '10.0.0.0/24', '2001:db8::/32'])
        ->and($settings->geofence)->toBeNull()
        ->and($settings->workday_close_time)->toBe('22:15');
});

it('AttendanceSettingsPageTest: the form starts from the stored settings', function (): void {
    $settings = app(AttendanceSettings::class);
    $settings->geofence = ['latitude' => 23.588, 'longitude' => 58.3829, 'radius_metres' => 250];
    $settings->save();

    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->assertFormSet([
            'grace_minutes'      => 10,
            'geofence_enabled'   => true,
            'geofence_latitude'  => 23.588,
            'geofence_longitude' => 58.3829,
            'geofence_radius'    => 250,
        ]);
});

it('AttendanceSettingsPageTest: each field rejects bad input with a form error', function (string $field, mixed $value): void {
    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm([$field => $value]))
        ->call('save')
        ->assertHasFormErrors([$field]);

    expect(app(AttendanceSettings::class)->grace_minutes)->toBe(10);
})->with([
    'grace below zero'           => ['grace_minutes', -1],
    'grace above 120'            => ['grace_minutes', 121],
    'grace not whole'            => ['grace_minutes', 5.5],
    'grace empty'                => ['grace_minutes', null],
    'normal multiplier below 1'  => ['overtime_multiplier_normal', 0.99],
    'rest multiplier below 1'    => ['overtime_multiplier_rest', 0.5],
    'holiday multiplier above 5' => ['overtime_multiplier_holiday', 5.01],
    'multiplier three decimals'  => ['overtime_multiplier_normal', 1.255],
    'multiplier not a number'    => ['overtime_multiplier_normal', 'abc'],
    'minimum overtime below 0'   => ['minimum_overtime_minutes', -5],
    'minimum overtime above 480' => ['minimum_overtime_minutes', 481],
    'close time not a time'      => ['workday_close_time', '25:99'],
    'close time empty'           => ['workday_close_time', null],
]);

it('AttendanceSettingsPageTest: an invalid IP or CIDR entry is reported on the field', function (array $entries, int $badIndex): void {
    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm(['allowed_ip_ranges' => $entries]))
        ->call('save')
        ->assertHasFormErrors(['allowed_ip_ranges.'.$badIndex]);

    expect(app(AttendanceSettings::class)->allowed_ip_ranges)->toBe([]);
})->with([
    'invalid IP'            => [['203.0.113.7', 'not-an-ip'], 1],
    'IPv4 out of range'     => [['256.1.1.1'], 0],
    'IPv4 mask too long'    => [['10.0.0.0/33'], 0],
    'IPv6 mask too long'    => [['2001:db8::/129'], 0],
    'mask not a number'     => [['10.0.0.0/abc'], 0],
    'CIDR with bad address' => [['10.0.0/24'], 0],
]);

it('AttendanceSettingsPageTest: the boundary values are accepted', function (): void {
    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm([
            'grace_minutes'               => 0,
            'overtime_multiplier_normal'  => 1,
            'overtime_multiplier_rest'    => 5,
            'overtime_multiplier_holiday' => 1.01,
            'minimum_overtime_minutes'    => 480,
            'allowed_ip_ranges'           => ['0.0.0.0/0', '::1', '10.0.0.0/32'],
        ]))
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(AttendanceSettings::class)->minimum_overtime_minutes)->toBe(480);
});

it('AttendanceSettingsPageTest: the IP rule accepts addresses and CIDR ranges only', function (): void {
    $rule = new IpAddressOrCidr;

    foreach (['1.2.3.4', ' 1.2.3.4 ', '192.168.0.0/16', '::1', '2001:db8::1', 'fe80::/10', '1.2.3.4/0', '1.2.3.4/32'] as $valid) {
        $failed = false;
        $rule->validate('ip', $valid, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse($valid);
    }

    foreach (['', 'abc', '1.2.3', '1.2.3.4/33', '1.2.3.4/-1', '1.2.3.4/', '1.2.3.4/1/2', '::1/129', '1.2.3.4/1e1', '/24', '1.2.3.4 /24', 12] as $invalid) {
        $failed = false;
        $rule->validate('ip', $invalid, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue(var_export($invalid, true));
    }
});

it('AttendanceSettingsPageTest: the geofence is saved as latitude, longitude and radius when switched on', function (): void {
    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm([
            'geofence_enabled'   => true,
            'geofence_latitude'  => 23.588,
            'geofence_longitude' => 58.3829,
            'geofence_radius'    => 300,
        ]))
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(AttendanceSettings::class)->geofence)->toBe([
        'latitude'      => 23.588,
        'longitude'     => 58.3829,
        'radius_metres' => 300.0,
    ]);
});

it('AttendanceSettingsPageTest: switching the geofence off stores null and leaves no toggle in the data', function (): void {
    $settings = app(AttendanceSettings::class);
    $settings->geofence = ['latitude' => 23.588, 'longitude' => 58.3829, 'radius_metres' => 250];
    $settings->save();

    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm([
            'geofence_enabled'   => false,
            // Left over from before the switch: must not be kept.
            'geofence_latitude'  => 23.588,
            'geofence_longitude' => 58.3829,
            'geofence_radius'    => 250,
        ]))
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(AttendanceSettings::class)->geofence)->toBeNull();

    $stored = DB::table('settings')->where('group', 'attendance')->pluck('payload', 'name');

    expect($stored->keys()->all())->not->toContain('geofence_enabled', 'geofence_latitude', 'geofence_radius')
        ->and($stored['geofence'])->toBe('null');
});

it('AttendanceSettingsPageTest: a partial or out-of-range geofence can never be saved', function (array $geofence, array $errors): void {
    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm(['geofence_enabled' => true, ...$geofence]))
        ->call('save')
        ->assertHasFormErrors($errors);

    expect(app(AttendanceSettings::class)->geofence)->toBeNull();
})->with([
    'nothing filled'       => [[], ['geofence_latitude', 'geofence_longitude', 'geofence_radius']],
    'latitude missing'     => [['geofence_longitude' => 58.0, 'geofence_radius' => 100], ['geofence_latitude']],
    'longitude missing'    => [['geofence_latitude' => 23.0, 'geofence_radius' => 100], ['geofence_longitude']],
    'radius missing'       => [['geofence_latitude' => 23.0, 'geofence_longitude' => 58.0], ['geofence_radius']],
    'latitude above 90'    => [['geofence_latitude' => 90.5, 'geofence_longitude' => 58.0, 'geofence_radius' => 100], ['geofence_latitude']],
    'latitude below -90'   => [['geofence_latitude' => -91, 'geofence_longitude' => 58.0, 'geofence_radius' => 100], ['geofence_latitude']],
    'longitude above 180'  => [['geofence_latitude' => 23.0, 'geofence_longitude' => 181, 'geofence_radius' => 100], ['geofence_longitude']],
    'longitude below -180' => [['geofence_latitude' => 23.0, 'geofence_longitude' => -180.5, 'geofence_radius' => 100], ['geofence_longitude']],
    'radius zero'          => [['geofence_latitude' => 23.0, 'geofence_longitude' => 58.0, 'geofence_radius' => 0], ['geofence_radius']],
    'radius too large'     => [['geofence_latitude' => 23.0, 'geofence_longitude' => 58.0, 'geofence_radius' => 50001], ['geofence_radius']],
]);

it('AttendanceSettingsPageTest: the geofence boundaries are accepted', function (): void {
    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm(['geofence_enabled' => true, 'geofence_latitude' => -90, 'geofence_longitude' => 180, 'geofence_radius' => 50000]))
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(AttendanceSettings::class)->geofence)->toBe(['latitude' => -90.0, 'longitude' => 180.0, 'radius_metres' => 50000.0]);
});

it('AttendanceSettingsPageTest: a saved geofence is always complete for the self check-in service', function (): void {
    attendanceSettingsUser();

    Livewire::test(ManageAttendanceSettings::class)
        ->fillForm(attendanceSettingsForm(['geofence_enabled' => true, 'geofence_latitude' => 23.5, 'geofence_longitude' => 58.4, 'geofence_radius' => 120]))
        ->call('save');

    expect(app(AttendanceSettings::class)->geofence)->toHaveKeys(['latitude', 'longitude', 'radius_metres']);
});

it('AttendanceSettingsPageTest: the page is forbidden without its permission and the key is the one Shield generates', function (): void {
    expect(array_keys(FilamentShield::getPages()[ManageAttendanceSettings::class]['permissions']))->toBe(['page_attendance_manage_attendance_settings']);

    attendanceSettingsUser(permitted: false);
    expect(ManageAttendanceSettings::canAccess())->toBeFalse();
    Livewire::test(ManageAttendanceSettings::class)->assertForbidden();

    attendanceSettingsUser();
    expect(ManageAttendanceSettings::canAccess())->toBeTrue()
        ->and(ManageAttendanceSettings::getSlug())->toBe('attendances/settings');
});

it('AttendanceSettingsPageTest: the page renders in Arabic and every string exists in both languages', function (): void {
    attendanceSettingsUser();

    $keys = [
        'navigation', 'title', 'sections.rules', 'sections.self_checkin', 'sections.geofence',
        'fields.grace_minutes', 'fields.grace_minutes_hint', 'fields.overtime_multiplier_normal', 'fields.overtime_multiplier_rest',
        'fields.overtime_multiplier_holiday', 'fields.multiplier_hint', 'fields.minimum_overtime_minutes', 'fields.minimum_overtime_hint',
        'fields.workday_close_time', 'fields.workday_close_time_hint', 'fields.allow_self_checkin', 'fields.allowed_ip_ranges',
        'fields.allowed_ip_ranges_hint', 'fields.geofence_enabled', 'fields.geofence_hint', 'fields.latitude', 'fields.longitude',
        'fields.radius_metres', 'errors.invalid_ip',
    ];

    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);

        foreach ($keys as $key) {
            expect(__('attendances::attendances.settings.'.$key))->not->toBe('attendances::attendances.settings.'.$key);
        }
    }

    app()->setLocale('en');
    expect(__('attendances::attendances.settings.fields.allowed_ip_ranges_hint'))->toContain('real address');

    app()->setLocale('ar');
    Livewire::test(ManageAttendanceSettings::class)
        ->assertSee(__('attendances::attendances.settings.fields.grace_minutes'))
        ->assertSee(__('attendances::attendances.settings.fields.geofence_enabled'));
    app()->setLocale('en');
});

it('AttendanceSettingsPageTest: the invalid IP message names the value', function (): void {
    attendanceSettingsUser();

    $failure = null;
    (new IpAddressOrCidr)->validate('ip', 'bogus', function (string $message) use (&$failure): void {
        $failure = $message;
    });

    expect($failure)->toContain('bogus');
});

it('AttendanceSettingsPageTest: the save step itself refuses an enabled geofence that is incomplete', function (): void {
    $page = new ManageAttendanceSettings;
    $method = new ReflectionMethod($page, 'mutateFormDataBeforeSave');

    $data = ['grace_minutes' => 10, 'minimum_overtime_minutes' => 30, 'overtime_multiplier_normal' => 1.25, 'overtime_multiplier_rest' => 1.5, 'overtime_multiplier_holiday' => 1.5, 'geofence_enabled' => true, 'geofence_latitude' => 23.5];

    expect(fn () => $method->invoke($page, $data))->toThrow(ValidationException::class);
});
