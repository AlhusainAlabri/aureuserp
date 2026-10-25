<?php

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\AttendanceFixtures;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Filament\Pages\ImportAttendance;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Models\ImportBatch;
use Webkul\Attendance\Services\AttendanceImporter;
use Webkul\Attendance\Services\ImportPreview;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches']);
    AttendanceFixtures::useLocalTimezone();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * @param  list<list<mixed>>  $rows
 */
function importCsv(array $rows, string $delimiter = ','): string
{
    return implode("\n", array_map(
        fn (array $row): string => implode($delimiter, array_map(fn ($cell): string => (string) $cell, $row)),
        $rows,
    ))."\n";
}

/**
 * @param  list<list<mixed>>  $rows
 */
function importTempFile(string $contents, string $extension): string
{
    $path = tempnam(sys_get_temp_dir(), 'att-import-').'.'.$extension;
    file_put_contents($path, $contents);

    return $path;
}

/**
 * @param  list<list<mixed>>  $rows
 */
function importXlsx(array $rows): string
{
    $export = new class($rows) implements FromArray
    {
        /**
         * @param  list<list<mixed>>  $rows
         */
        public function __construct(private array $rows) {}

        /**
         * @return list<list<mixed>>
         */
        public function array(): array
        {
            return $this->rows;
        }
    };

    return importTempFile(Excel::raw($export, ExcelWriter::XLSX), 'xlsx');
}

function importEmployee(?string $barcode, array $attributes = []): Employee
{
    $company = AttendanceFixtures::company();

    return AttendanceFixtures::employee($company, AttendanceFixtures::calendar($company), array_merge(['barcode' => $barcode], $attributes));
}

function importMapping(?int $direction = null): array
{
    return ['device_user_id' => 0, 'timestamp' => 1, 'direction' => $direction];
}

function importUser(): User
{
    return User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
}

function importPageUser(bool $permitted): User
{
    $user = importUser();
    $permission = array_key_first(FilamentShield::getPages()[ImportAttendance::class]['permissions']);

    Permission::findOrCreate($permission, 'web');

    if ($permitted) {
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

function importPunchesOf(Employee $employee): array
{
    return AttendancePunch::query()
        ->where('employee_id', $employee->id)
        ->orderBy('punched_at')
        ->get()
        ->map(fn (AttendancePunch $punch): string => $punch->punched_at->format('Y-m-d H:i:s'))
        ->all();
}

it('AttendanceImportTest: CSV and XLSX files read the same rows', function (): void {
    $rows = [
        ['User', 'Time', 'State'],
        ['1001', '2026-11-02 08:00:00', 'in'],
        ['1002', '2026-11-02 08:05:00', 'out'],
    ];

    $csv = app(AttendanceImporter::class)->read(importTempFile(importCsv($rows), 'csv'), 'csv');
    $xlsx = app(AttendanceImporter::class)->read(importXlsx($rows), 'xlsx');

    expect($csv['headers'])->toBe(['User', 'Time', 'State'])
        ->and($csv['rows'])->toHaveCount(3)
        ->and($csv['rows'][1][0])->toEqual(1001)
        ->and($csv['rows'][1][1])->toBe('2026-11-02 08:00:00')
        ->and($xlsx['headers'])->toBe($csv['headers'])
        ->and($xlsx['rows'])->toHaveCount(3)
        ->and(array_map(fn (array $row): array => array_map(fn ($cell): string => (string) $cell, $row), $xlsx['rows']))
        ->toBe(array_map(fn (array $row): array => array_map(fn ($cell): string => (string) $cell, $row), $csv['rows']));
});

it('AttendanceImportTest: reads semicolon separated CSV and an empty file', function (): void {
    $semicolon = app(AttendanceImporter::class)->read(
        importTempFile(importCsv([['User', 'Time'], ['7', '2026-11-02 08:00:00']], ';'), 'csv'),
        'csv',
    );

    expect($semicolon['headers'])->toBe(['User', 'Time'])
        ->and($semicolon['rows'][1][1])->toBe('2026-11-02 08:00:00');

    expect(fn () => app(AttendanceImporter::class)->read(importTempFile('', 'csv'), 'csv'))
        ->toThrow(AttendanceException::class, __('attendances::attendances.import.errors.empty'));
});

it('AttendanceImportTest: refuses unsupported extensions and files with too many rows', function (): void {
    expect(fn () => app(AttendanceImporter::class)->read(importTempFile('x', 'pdf'), 'pdf'))
        ->toThrow(AttendanceException::class, __('attendances::attendances.import.errors.unsupported_type'));

    $rows = array_fill(0, AttendanceImporter::MAX_ROWS + 2, ['1', '2026-11-02 08:00:00']);

    expect(fn () => app(AttendanceImporter::class)->read(importTempFile(importCsv($rows), 'csv'), 'csv'))
        ->toThrow(AttendanceException::class, __('attendances::attendances.import.errors.too_many_rows', ['max' => AttendanceImporter::MAX_ROWS]));
});

it('AttendanceImportTest: maps columns with and without a header row', function (): void {
    $employee = importEmployee('1001');
    $importer = app(AttendanceImporter::class);

    $withHeader = $importer->preview([
        ['User', 'Time'],
        ['1001', '2026-11-02 08:00:00'],
    ], importMapping(), true);

    $withoutHeader = $importer->preview([
        ['1001', '2026-11-02 08:00:00'],
    ], importMapping(), false);

    // The same file read with the wrong header assumption turns the header into an invalid row.
    $headerTreatedAsData = $importer->preview([
        ['User', 'Time'],
        ['1001', '2026-11-02 08:00:00'],
    ], importMapping(), false);

    $swapped = $importer->preview([
        ['2026-11-02 08:00:00', '1001'],
    ], ['device_user_id' => 1, 'timestamp' => 0, 'direction' => null], false);

    expect($withHeader)->toBeInstanceOf(ImportPreview::class)
        ->and($withHeader->matched)->toHaveCount(1)
        ->and($withHeader->matched[0]['employee_id'])->toBe($employee->id)
        ->and($withHeader->invalidRows)->toBe(0)
        ->and($withoutHeader->matched)->toHaveCount(1)
        ->and($headerTreatedAsData->matched)->toHaveCount(1)
        ->and($headerTreatedAsData->invalidRows)->toBe(1)
        ->and($swapped->matched)->toHaveCount(1);
});

it('AttendanceImportTest: reads timestamps in the employee zone and keeps UTC', function (): void {
    $muscat = importEmployee('1001');
    $kolkata = importEmployee('1002', ['time_zone' => 'Asia/Kolkata']);

    $preview = app(AttendanceImporter::class)->preview([
        ['1001', '2026-11-02 08:00:00'],
        ['1002', '2026-11-02 08:00:00'],
    ], importMapping(), false);

    $byEmployee = collect($preview->matched)->keyBy('employee_id');

    expect($byEmployee[$muscat->id]['at']->format('Y-m-d H:i:s'))->toBe('2026-11-02 04:00:00')
        ->and($byEmployee[$muscat->id]['at']->timezoneName)->toBe('UTC')
        ->and($byEmployee[$kolkata->id]['at']->format('Y-m-d H:i:s'))->toBe('2026-11-02 02:30:00');

    app(AttendanceImporter::class)->commit($preview, importUser(), 'zones.csv');

    expect(importPunchesOf($muscat))->toBe(['2026-11-02 04:00:00'])
        ->and(importPunchesOf($kolkata))->toBe(['2026-11-02 02:30:00']);
});

it('AttendanceImportTest: reads Excel serial dates as local time', function (): void {
    $employee = importEmployee('1001');
    $serial = SpreadsheetDate::dateTimeToExcel(new DateTime('2026-11-02 08:30:00'));

    $file = app(AttendanceImporter::class)->read(importXlsx([['User', 'Time'], ['1001', $serial]]), 'xlsx');

    expect($file['rows'][1][1])->toBeFloat();

    $preview = app(AttendanceImporter::class)->preview($file['rows'], importMapping(), true);

    expect($preview->matched)->toHaveCount(1)
        ->and($preview->matched[0]['employee_id'])->toBe($employee->id)
        ->and($preview->matched[0]['at']->format('Y-m-d H:i:s'))->toBe('2026-11-02 04:30:00');
});

it('AttendanceImportTest: the direction column is optional and tolerant', function (): void {
    importEmployee('1001');

    $preview = app(AttendanceImporter::class)->preview([
        ['1001', '2026-11-02 08:00:00', 'IN'],
        ['1001', '2026-11-02 09:00:00', 'out'],
        ['1001', '2026-11-02 10:00:00', '1'],
        ['1001', '2026-11-02 11:00:00', 0],
        ['1001', '2026-11-02 12:00:00', 'check'],
        ['1001', '2026-11-02 13:00:00', ''],
    ], importMapping(2), false);

    expect(array_map(fn (array $row) => $row['direction'], $preview->matched))->toBe([
        PunchDirection::In,
        PunchDirection::Out,
        PunchDirection::In,
        PunchDirection::Out,
        null,
        null,
    ]);

    $without = app(AttendanceImporter::class)->preview([['1001', '2026-11-02 08:00:00', 'in']], importMapping(), false);

    expect($without->matched[0]['direction'])->toBeNull();
});

it('AttendanceImportTest: matches device ids on the barcode after trimming, as case-sensitive text', function (): void {
    $upper = importEmployee('AB12');
    $lower = importEmployee('ab12');
    $padded = importEmployee(' 55 ');
    $inactive = importEmployee('77', ['is_active' => false]);

    $preview = app(AttendanceImporter::class)->preview([
        ['AB12', '2026-11-02 08:00:00'],
        ['ab12', '2026-11-02 08:00:00'],
        ['  55', '2026-11-02 08:00:00'],
        [77.0, '2026-11-02 08:00:00'],
        ['99', '2026-11-02 08:00:00'],
        ['99', '2026-11-02 09:00:00'],
        [' 98 ', '2026-11-02 09:00:00'],
    ], importMapping(), false);

    expect(collect($preview->matched)->pluck('employee_id')->sort()->values()->all())
        ->toBe(collect([$upper->id, $lower->id, $padded->id, $inactive->id])->sort()->values()->all())
        ->and($preview->unmatchedDeviceIds)->toBe(['99', '98'])
        ->and($preview->ambiguousDeviceIds)->toBe([])
        ->and($preview->invalidRows)->toBe(0);
});

it('AttendanceImportTest: a barcode shared by several employees is ambiguous and never guessed', function (): void {
    importEmployee('300');
    importEmployee('300');
    $single = importEmployee('301');

    $preview = app(AttendanceImporter::class)->preview([
        ['300', '2026-11-02 08:00:00'],
        ['300', '2026-11-02 17:00:00'],
        ['301', '2026-11-02 08:00:00'],
    ], importMapping(), false);

    expect($preview->matched)->toHaveCount(1)
        ->and($preview->matched[0]['employee_id'])->toBe($single->id)
        ->and($preview->ambiguousDeviceIds)->toBe(['300'])
        ->and($preview->unmatchedDeviceIds)->toBe([]);
});

it('AttendanceImportTest: lists each unmatched device id once', function (): void {
    importEmployee('1001');

    $preview = app(AttendanceImporter::class)->preview([
        ['5000', '2026-11-02 08:00:00'],
        ['5000', '2026-11-02 17:00:00'],
        ['5001', '2026-11-02 08:00:00'],
        ['1001', '2026-11-02 08:00:00'],
    ], importMapping(), false);

    expect($preview->unmatchedDeviceIds)->toBe(['5000', '5001'])
        ->and($preview->matched)->toHaveCount(1);
});

it('AttendanceImportTest: counts blank and unparsable timestamps as invalid rows and skips empty lines', function (): void {
    importEmployee('1001');

    $preview = app(AttendanceImporter::class)->preview([
        ['1001', ''],
        ['1001', 'not a date'],
        ['1001', null],
        ['', '2026-11-02 08:00:00'],
        [null, null],
        ['', ''],
        ['1001', '2026-11-02 08:00:00'],
    ], importMapping(), false);

    expect($preview->invalidRows)->toBe(4)
        ->and($preview->matched)->toHaveCount(1)
        ->and($preview->totalRows)->toBe(5);
});

it('AttendanceImportTest: counts repeated rows once and existing device punches as duplicates', function (): void {
    $employee = importEmployee('1001');
    AttendanceFixtures::punch($employee, '2026-11-02 08:00:00', PunchSource::Device);
    // A manual punch at the same instant is another source: it does not make the device row a duplicate.
    AttendanceFixtures::punch($employee, '2026-11-02 09:00:00', PunchSource::Manual);

    $preview = app(AttendanceImporter::class)->preview([
        ['1001', '2026-11-02 08:00:00'],
        ['1001', '2026-11-02 09:00:00'],
        ['1001', '2026-11-02 09:00:00'],
        ['1001', '2026-11-02 09:00:00.400'],
        ['1001', '2026-11-02 17:00:00'],
    ], importMapping(), false);

    expect($preview->matched)->toHaveCount(2)
        ->and($preview->duplicates)->toBe(3)
        ->and(collect($preview->matched)->map(fn (array $row): string => $row['at']->format('H:i'))->all())->toBe(['05:00', '13:00']);
});

it('AttendanceImportTest: commit stores one batch, device punches and recalculated days', function (): void {
    $employee = importEmployee('1001');
    $other = importEmployee('1002');
    $admin = importUser();

    $preview = app(AttendanceImporter::class)->preview([
        ['1001', '2026-11-02 08:00:00', 'in'],
        ['1001', '2026-11-02 17:00:00', 'out'],
        ['1002', '2026-11-02 08:30:00', ''],
        ['9999', '2026-11-02 08:30:00', ''],
        ['1001', 'garbage', ''],
        ['1001', '2026-11-02 08:00:00', 'in'],
    ], importMapping(2), false);

    $batch = app(AttendanceImporter::class)->commit($preview, $admin, 'week-44.csv');

    expect($batch)->toBeInstanceOf(ImportBatch::class)
        ->and($batch->file_name)->toBe('week-44.csv')
        ->and($batch->total_rows)->toBe(6)
        ->and($batch->matched_rows)->toBe(3)
        ->and($batch->duplicate_rows)->toBe(1)
        ->and($batch->invalid_rows)->toBe(1)
        ->and($batch->unmatched_device_ids)->toBe(['9999'])
        ->and($batch->created_by)->toBe($admin->id)
        ->and(ImportBatch::query()->count())->toBe(1)
        ->and($batch->punches()->count())->toBe(3);

    $punch = AttendancePunch::query()->where('employee_id', $employee->id)->orderBy('punched_at')->first();

    expect($punch->source)->toBe(PunchSource::Device)
        ->and($punch->direction)->toBe(PunchDirection::In)
        ->and($punch->import_batch_id)->toBe($batch->id)
        ->and($punch->device_ref)->toBe('1001');

    $day = AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', '2026-11-02')->first();
    $otherDay = AttendanceDay::query()->where('employee_id', $other->id)->whereDate('date', '2026-11-02')->first();

    expect($day->status)->toBe(DayStatus::Present)
        ->and($day->first_in->format('H:i'))->toBe('04:00')
        ->and($day->last_out->format('H:i'))->toBe('13:00')
        ->and($day->worked_minutes)->toBe(480)
        ->and($otherDay->status)->toBe(DayStatus::Present)
        ->and($otherDay->last_out)->toBeNull();
});

it('AttendanceImportTest: unsorted device rows still give the right first in and last out', function (): void {
    $employee = importEmployee('1001');

    $preview = app(AttendanceImporter::class)->preview([
        ['1001', '2026-11-02 17:05:00', 'out'],
        ['1001', '2026-11-02 12:30:00', 'out'],
        ['1001', '2026-11-02 07:58:00', 'out'],
        ['1001', '2026-11-02 13:00:00', 'in'],
    ], importMapping(2), false);

    app(AttendanceImporter::class)->commit($preview, importUser(), 'unsorted.csv');

    $day = AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', '2026-11-02')->first();

    // The stored direction is informational: the earliest punch is "in" and the latest is "out".
    expect($day->first_in->format('H:i'))->toBe('03:58')
        ->and($day->last_out->format('H:i'))->toBe('13:05')
        ->and(importPunchesOf($employee))->toHaveCount(4);
});

it('AttendanceImportTest: importing the same file twice creates no new punches and reports duplicates', function (): void {
    $employee = importEmployee('1001');
    $rows = [
        ['1001', '2026-11-02 08:00:00'],
        ['1001', '2026-11-02 17:00:00'],
        ['1001', '2026-11-03 08:00:00'],
    ];
    $importer = app(AttendanceImporter::class);
    $admin = importUser();

    $first = $importer->commit($importer->preview($rows, importMapping(), false), $admin, 'file.csv');
    $second = $importer->commit($importer->preview($rows, importMapping(), false), $admin, 'file.csv');

    expect($first->matched_rows)->toBe(3)
        ->and($first->duplicate_rows)->toBe(0)
        ->and($second->id)->not->toBe($first->id)
        ->and($second->matched_rows)->toBe(0)
        ->and($second->duplicate_rows)->toBe($first->matched_rows)
        ->and($second->punches()->count())->toBe(0)
        ->and(AttendancePunch::query()->where('employee_id', $employee->id)->count())->toBe(3)
        ->and(AttendanceDay::query()->where('employee_id', $employee->id)->count())->toBe(2)
        ->and(ImportBatch::query()->count())->toBe(2);
});

it('AttendanceImportTest: a preview that went stale still never double counts at commit', function (): void {
    $employee = importEmployee('1001');
    $importer = app(AttendanceImporter::class);

    $preview = $importer->preview([['1001', '2026-11-02 08:00:00']], importMapping(), false);

    // Somebody imports the same punch between the preview and the confirmation.
    $importer->commit($preview, importUser(), 'first.csv');
    $batch = $importer->commit($preview, importUser(), 'second.csv');

    expect($batch->matched_rows)->toBe(0)
        ->and($batch->duplicate_rows)->toBe(1)
        ->and(AttendancePunch::query()->where('employee_id', $employee->id)->count())->toBe(1);
});

it('AttendanceImportTest: the permission key is the one Shield generates for the page', function (): void {
    $permissions = FilamentShield::getPages()[ImportAttendance::class]['permissions'];

    expect(array_keys($permissions))->toBe(['page_attendance_import_attendance']);
});

it('AttendanceImportTest: forbids the page without its permission', function (): void {
    importPageUser(false);

    expect(ImportAttendance::canAccess())->toBeFalse();

    Livewire::test(ImportAttendance::class)->assertForbidden();
});

it('AttendanceImportTest: the page walks upload, mapping, preview and confirmation', function (): void {
    Storage::fake('local');
    $employee = importEmployee('1001');
    importPageUser(true);

    expect(ImportAttendance::canAccess())->toBeTrue();

    $csv = importCsv([
        ['User', 'Time', 'State'],
        ['1001', '2026-11-02 08:00:00', 'in'],
        ['1001', '2026-11-02 17:00:00', 'out'],
        ['4040', '2026-11-02 08:00:00', 'in'],
    ]);

    $component = Livewire::test(ImportAttendance::class)
        ->assertSuccessful()
        ->assertSet('step', 1)
        ->fillForm(['file' => UploadedFile::fake()->createWithContent('punches.csv', $csv)])
        ->call('uploadFile')
        ->assertHasNoFormErrors()
        ->assertSet('step', 2)
        ->assertSet('fileName', 'punches.csv')
        ->assertSet('headers', ['User', 'Time', 'State']);

    $component
        ->fillForm(['has_header' => true, 'device_user_id' => 0, 'timestamp' => 1, 'direction' => 2])
        ->call('showPreview')
        ->assertHasNoFormErrors()
        ->assertSet('step', 3)
        ->assertSee('4040')
        ->assertSet('summary.new_punches', 2)
        ->assertSet('summary.unmatched', ['4040']);

    expect(ImportBatch::query()->count())->toBe(0);

    $component->call('confirm')->assertNotified()->assertSet('step', 1);

    $batch = ImportBatch::query()->firstOrFail();

    expect($batch->file_name)->toBe('punches.csv')
        ->and($batch->matched_rows)->toBe(2)
        ->and($batch->unmatched_device_ids)->toBe(['4040'])
        ->and($batch->created_by)->toBe(auth()->id())
        ->and(importPunchesOf($employee))->toBe(['2026-11-02 04:00:00', '2026-11-02 13:00:00'])
        ->and(Storage::disk('local')->allFiles('attendance-imports'))->toBe([]);
});

it('AttendanceImportTest: the page refuses an unreadable mapping and goes back a step', function (): void {
    Storage::fake('local');
    importEmployee('1001');
    importPageUser(true);

    $csv = importCsv([['1001', '2026-11-02 08:00:00']]);

    Livewire::test(ImportAttendance::class)
        ->fillForm(['file' => UploadedFile::fake()->createWithContent('punches.csv', $csv)])
        ->call('uploadFile')
        ->assertSet('step', 2)
        ->fillForm(['has_header' => false, 'device_user_id' => 0, 'timestamp' => null])
        ->call('showPreview')
        ->assertHasFormErrors(['timestamp'])
        ->assertSet('step', 2)
        ->fillForm(['device_user_id' => 1, 'timestamp' => 1])
        ->call('showPreview')
        ->assertHasFormErrors(['timestamp'])
        ->assertSet('step', 2)
        ->fillForm(['device_user_id' => 0, 'timestamp' => 1])
        ->call('showPreview')
        ->assertSet('step', 3)
        ->call('back')
        ->assertSet('step', 2)
        ->call('startOver')
        ->assertSet('step', 1);

    expect(Storage::disk('local')->allFiles('attendance-imports'))->toBe([]);
});

it('AttendanceImportTest: the page does not accept other file types', function (): void {
    Storage::fake('local');
    importPageUser(true);

    Livewire::test(ImportAttendance::class)
        ->fillForm(['file' => UploadedFile::fake()->createWithContent('punches.pdf', '%PDF-1.4')])
        ->call('uploadFile')
        ->assertHasFormErrors(['file'])
        ->assertSet('step', 1);
});

it('AttendanceImportTest: the employee form explains what the barcode is for', function (): void {
    foreach (['en' => 'Attendance device user ID', 'ar' => 'رقم المستخدم في جهاز الحضور'] as $locale => $text) {
        expect(__('employees::filament/resources/employee.form.tabs.settings.fields.badge-id-helper', [], $locale))->toBe($text);
    }

    expect(file_get_contents(base_path('plugins/webkul/employees/src/Filament/Resources/EmployeeResource.php')))
        ->toContain("fields.badge-id-helper'");
});
