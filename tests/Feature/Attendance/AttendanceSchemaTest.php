<?php

use App\Support\ModuleLauncher\ModuleLauncherItems;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Models\ImportBatch;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\PluginManager\Package;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();
});

it('AttendanceSchemaTest: the four tables have every column of the spec', function (): void {
    expect(Schema::getColumnListing('attendance_punches'))->toContain(
        'id', 'employee_id', 'punched_at', 'source', 'direction', 'device_ref', 'import_batch_id',
        'ip', 'latitude', 'longitude', 'creator_id', 'created_at', 'updated_at',
    )->and(Schema::getColumnListing('attendance_days'))->toContain(
        'id', 'employee_id', 'date', 'first_in', 'last_out', 'worked_minutes', 'expected_minutes',
        'late_minutes', 'early_leave_minutes', 'overtime_minutes', 'status', 'is_manual_override',
        'override_note', 'edited_by', 'leave_id', 'created_at', 'updated_at',
    )->and(Schema::getColumnListing('attendance_overtime_requests'))->toContain(
        'id', 'employee_id', 'date', 'minutes', 'day_type', 'multiplier', 'status',
        'decided_by', 'decided_at', 'note', 'created_at', 'updated_at',
    )->and(Schema::getColumnListing('attendance_import_batches'))->toContain(
        'id', 'file_name', 'total_rows', 'matched_rows', 'duplicate_rows', 'invalid_rows',
        'unmatched_device_ids', 'created_by', 'created_at', 'updated_at',
    );
});

it('AttendanceSchemaTest: duplicate punches for one employee, instant and source are rejected', function (): void {
    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    AttendanceFixtures::punch($employee, '2026-11-02 08:00:00', PunchSource::Device);

    expect(fn () => AttendanceFixtures::punch($employee, '2026-11-02 08:00:00', PunchSource::Device))
        ->toThrow(QueryException::class);

    // A different source at the same instant is a different row.
    expect(AttendanceFixtures::punch($employee, '2026-11-02 08:00:00', PunchSource::Manual))
        ->toBeInstanceOf(AttendancePunch::class);
});

it('AttendanceSchemaTest: one attendance day per employee and date', function (): void {
    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    AttendanceDay::query()->create(['employee_id' => $employee->id, 'date' => '2026-11-02', 'status' => DayStatus::Present]);

    expect(fn () => DB::table('attendance_days')->insert([
        'employee_id' => $employee->id, 'date' => '2026-11-02', 'status' => 'absent',
    ]))->toThrow(QueryException::class);
});

it('AttendanceSchemaTest: one overtime request per employee and date', function (): void {
    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    OvertimeRequest::query()->create([
        'employee_id' => $employee->id, 'date' => '2026-11-02', 'minutes' => 60,
        'day_type'    => OvertimeDayType::Normal, 'multiplier' => 1.25,
    ]);

    expect(fn () => DB::table('attendance_overtime_requests')->insert([
        'employee_id' => $employee->id, 'date' => '2026-11-02', 'minutes' => 30,
        'day_type'    => 'normal', 'multiplier' => 1.25, 'status' => 'pending',
    ]))->toThrow(QueryException::class);
});

it('AttendanceSchemaTest: models cast their attributes and relate to the employee', function (): void {
    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    $punch = AttendanceFixtures::punch($employee, '2026-11-02 08:00:00');
    $day = AttendanceDay::query()->create([
        'employee_id' => $employee->id, 'date' => '2026-11-02', 'first_in' => '2026-11-02 04:00:00',
        'status'      => DayStatus::Present, 'is_manual_override' => 1,
    ]);
    $request = OvertimeRequest::query()->create([
        'employee_id' => $employee->id, 'date' => '2026-11-02', 'minutes' => 60,
        'day_type'    => OvertimeDayType::Rest, 'multiplier' => 1.5,
    ]);
    $batch = ImportBatch::query()->create(['file_name' => 'a.csv', 'unmatched_device_ids' => ['7']]);

    expect($punch->fresh()->punched_at->toDateTimeString())->toBe('2026-11-02 04:00:00')
        ->and($punch->fresh()->source)->toBe(PunchSource::Manual)
        ->and($punch->employee->is($employee))->toBeTrue()
        ->and($day->fresh()->date->toDateString())->toBe('2026-11-02')
        ->and($day->fresh()->first_in)->not->toBeNull()
        ->and($day->fresh()->status)->toBe(DayStatus::Present)
        ->and($day->fresh()->is_manual_override)->toBeTrue()
        ->and($day->employee->is($employee))->toBeTrue()
        ->and($request->fresh()->day_type)->toBe(OvertimeDayType::Rest)
        ->and($request->fresh()->status)->toBe(OvertimeStatus::Pending)
        ->and($request->fresh()->multiplier)->toBe('1.50')
        ->and($request->employee->is($employee))->toBeTrue()
        ->and($batch->fresh()->unmatched_device_ids)->toBe(['7']);

    $employee->forceDelete();

    expect(AttendancePunch::query()->whereKey($punch->id)->exists())->toBeFalse()
        ->and(AttendanceDay::query()->whereKey($day->id)->exists())->toBeFalse()
        ->and(OvertimeRequest::query()->whereKey($request->id)->exists())->toBeFalse();
});

it('AttendanceSchemaTest: resolves the default attendance settings', function (): void {
    $settings = app(AttendanceSettings::class);

    expect($settings->grace_minutes)->toBe(10)
        ->and($settings->overtime_multiplier_normal)->toBe(1.25)
        ->and($settings->overtime_multiplier_rest)->toBe(1.5)
        ->and($settings->overtime_multiplier_holiday)->toBe(1.5)
        ->and($settings->minimum_overtime_minutes)->toBe(30)
        ->and($settings->allow_self_checkin)->toBeTrue()
        ->and($settings->allowed_ip_ranges)->toBe([])
        ->and($settings->geofence)->toBeNull()
        ->and($settings->workday_close_time)->toBe('23:30');
});

it('AttendanceSchemaTest: the enums are labelled in both locales', function (): void {
    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);

        foreach ([PunchSource::class, PunchDirection::class, DayStatus::class, OvertimeDayType::class, OvertimeStatus::class] as $enum) {
            foreach ($enum::cases() as $case) {
                expect($case->getLabel())->not->toContain('attendances::');
            }
        }
    }

    expect(PunchSource::cases())->toHaveCount(3)
        ->and(PunchDirection::cases())->toHaveCount(2)
        ->and(DayStatus::cases())->toHaveCount(5)
        ->and(OvertimeDayType::cases())->toHaveCount(3)
        ->and(OvertimeStatus::cases())->toHaveCount(3);
});

it('AttendanceSchemaTest: the plugin is installed and has a launcher colour', function (): void {
    expect(Package::isPluginInstalled('attendances'))->toBeTrue()
        ->and(ModuleLauncherItems::colorForIcon('icon-attendance'))->not->toBe('gray')
        ->and(ModuleLauncherItems::moduleIconOptions())->toHaveKey('icon-attendance')
        ->and(file_exists(resource_path('svg/attendance.svg')))->toBeTrue();
});

it('AttendanceSchemaTest: the fixtures build a Monday to Friday calendar', function (): void {
    $company = AttendanceFixtures::company();
    $calendar = AttendanceFixtures::calendar($company);
    $employee = AttendanceFixtures::employee($company, $calendar, ['barcode' => '42']);

    expect($calendar->attendance()->where('day_of_week', 'monday')->count())->toBe(3)
        ->and($calendar->attendance()->where('day_of_week', 'friday')->where('day_period', 'lunch')->count())->toBe(1)
        ->and($calendar->attendance()->whereIn('day_of_week', ['saturday', 'sunday'])->count())->toBe(0)
        ->and($employee->calendar_id)->toBe($calendar->id)
        ->and($employee->user_id)->not->toBeNull()
        ->and($employee->time_zone)->toBeNull()
        ->and($employee->barcode)->toBe('42');

    $holiday = AttendanceFixtures::holiday('2026-11-18', '2026-11-19');

    expect($holiday->time_type)->toBe('leave')
        ->and($holiday->calendar_id)->toBeNull();
});

it('AttendanceSchemaTest: the fixtures store punches in UTC', function (): void {
    $employee = AttendanceFixtures::employee(AttendanceFixtures::company(), null);

    $punch = AttendanceFixtures::punch($employee, '2026-11-02 23:30:00');

    expect($punch->punched_at->toDateTimeString())->toBe('2026-11-02 19:30:00');
});
