<?php

namespace Webkul\Attendance\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Excel as ExcelReader;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use Throwable;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Models\ImportBatch;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class AttendanceImporter
{
    /**
     * Most data rows one file may hold; larger files are refused rather than silently truncated.
     */
    public const MAX_ROWS = 20000;

    /**
     * Numeric cells above this are Excel date serials (20000 is 1954); smaller numbers are not dates.
     */
    protected const EXCEL_SERIAL_THRESHOLD = 20000;

    protected const LOOKUP_CHUNK = 500;

    /**
     * Punches inserted per query when committing.
     */
    protected const INSERT_CHUNK = 500;

    /**
     * Reads every row of a CSV or XLSX file. The first row is returned as `headers` too (stringified) so a
     * caller can offer it as column names; whether it is data is decided later by `preview()`.
     *
     * @return array{headers: list<string>, rows: list<array<int, mixed>>}
     *
     * @throws AttendanceException
     */
    public function read(string $path, string $extension): array
    {
        $type = match (strtolower($extension)) {
            'csv'   => ExcelReader::CSV,
            'xlsx'  => ExcelReader::XLSX,
            default => throw new AttendanceException(__('attendances::attendances.import.errors.unsupported_type')),
        };

        try {
            $sheets = Excel::toArray($this->importable($type === ExcelReader::CSV ? $this->detectDelimiter($path) : null), $path, null, $type);
        } catch (Throwable $exception) {
            report($exception);

            throw new AttendanceException(__('attendances::attendances.import.errors.unreadable'), previous: $exception);
        }

        $rows = [];

        foreach ($sheets[0] ?? [] as $row) {
            $row = array_values($row);

            if (! $this->isBlankRow($row)) {
                $rows[] = $row;
            }
        }

        if ($rows === []) {
            throw new AttendanceException(__('attendances::attendances.import.errors.empty'));
        }

        // Coarse guard: the first row may be a header, so only `preview()` can tell the data rows exactly.
        if (count($rows) > self::MAX_ROWS + 1) {
            throw $this->tooManyRows();
        }

        return [
            'headers' => array_map(fn (mixed $cell): string => $this->text($cell), $rows[0]),
            'rows'    => $rows,
        ];
    }

    /**
     * Works out what importing the rows would do, without writing anything.
     *
     * @param  list<array<int, mixed>>  $rows
     * @param  array{device_user_id: int, timestamp: int, direction: ?int}  $mapping
     */
    public function preview(array $rows, array $mapping, bool $hasHeader): ImportPreview
    {
        $employees = $this->employeesByBarcode();
        // The file holds the office device's clock, so every time is read in the application (company) zone;
        // the employee's own zone only decides which local day a punch belongs to, later, when days are recalculated.
        $zone = (string) config('app.timezone');

        $matched = [];
        $seen = [];
        $unmatched = [];
        $ambiguous = [];
        $duplicates = 0;
        $invalid = 0;
        $total = 0;

        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        foreach ($dataRows as $row) {
            $row = array_values($row);

            if ($this->isBlankRow($row)) {
                continue;
            }

            $total++;

            if ($total > self::MAX_ROWS) {
                throw $this->tooManyRows();
            }

            $deviceId = $this->text($row[$mapping['device_user_id']] ?? null);
            $candidates = $employees[$deviceId] ?? [];
            $at = $deviceId === '' ? null : $this->parseTimestamp($row[$mapping['timestamp']] ?? null, $zone);

            if ($at === null) {
                $invalid++;

                continue;
            }

            if (count($candidates) === 0) {
                $unmatched[$deviceId] = $deviceId;

                continue;
            }

            if (count($candidates) > 1) {
                $ambiguous[$deviceId] = $deviceId;

                continue;
            }

            $employeeId = $candidates[0]['id'];
            $key = $employeeId.'|'.$at->format('Y-m-d H:i:s');

            if (isset($seen[$key])) {
                $duplicates++;

                continue;
            }

            $seen[$key] = true;
            $matched[] = [
                'employee_id' => $employeeId,
                'at'          => $at,
                'direction'   => $mapping['direction'] === null ? null : $this->parseDirection($row[$mapping['direction']] ?? null),
                'device_ref'  => $deviceId,
            ];
        }

        $stored = $this->storedDevicePunches($matched);
        $new = [];

        foreach ($matched as $row) {
            if (isset($stored[$row['employee_id'].'|'.$row['at']->format('Y-m-d H:i:s')])) {
                $duplicates++;

                continue;
            }

            $new[] = $row;
        }

        return new ImportPreview(
            matched: $new,
            unmatchedDeviceIds: array_map('strval', array_values($unmatched)),
            duplicates: $duplicates,
            invalidRows: $invalid,
            ambiguousDeviceIds: array_map('strval', array_values($ambiguous)),
            totalRows: $total,
        );
    }

    /**
     * Stores the batch and its punches in one transaction. Punches are inserted in chunks (one lookup of the
     * existing keys and one `insert or ignore` per chunk, never a query per punch) and the days are left
     * alone until every chunk is in; then each employee-day that received a NEW punch is recalculated
     * exactly once. Punches stored since the preview was computed count as duplicates, and the unique index
     * on (employee, instant, source) stays the backstop.
     *
     * @throws AttendanceException
     */
    public function commit(ImportPreview $preview, User $by, string $fileName): ImportBatch
    {
        if ($preview->totalRows > self::MAX_ROWS) {
            throw $this->tooManyRows();
        }

        $recorder = app(PunchRecorder::class);
        $calculator = app(AttendanceDayCalculator::class);

        return DB::transaction(function () use ($preview, $by, $fileName, $recorder, $calculator): ImportBatch {
            $batch = ImportBatch::query()->create([
                'file_name'            => Str::limit($fileName, 190, ''),
                'total_rows'           => $preview->totalRows,
                'matched_rows'         => 0,
                'duplicate_rows'       => $preview->duplicates,
                'invalid_rows'         => $preview->invalidRows,
                'unmatched_device_ids' => array_values(array_unique([...$preview->unmatchedDeviceIds, ...$preview->ambiguousDeviceIds])),
                'created_by'           => $by->id,
            ]);

            $employees = Employee::query()
                ->whereIn('id', array_unique(array_column($preview->matched, 'employee_id')))
                ->get()
                ->keyBy('id');

            $created = 0;
            $duplicates = $preview->duplicates;
            $invalid = $preview->invalidRows;

            // Oldest first, so punch ids and the day rows created below follow time whatever the file order.
            $rows = [];

            foreach ($preview->matched as $row) {
                if ($employees->has($row['employee_id'])) {
                    $rows[] = $row;
                } else {
                    $invalid++;
                }
            }

            usort($rows, fn (array $a, array $b): int => [$a['at']->getTimestamp(), $a['employee_id']] <=> [$b['at']->getTimestamp(), $b['employee_id']]);

            $now = now()->format('Y-m-d H:i:s');
            $handled = [];
            $affectedDays = [];

            foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
                $stored = $this->storedDevicePunches($chunk);
                $insert = [];

                foreach ($chunk as $row) {
                    $key = $row['employee_id'].'|'.$row['at']->format('Y-m-d H:i:s');

                    if (isset($stored[$key]) || isset($handled[$key])) {
                        $duplicates++;

                        continue;
                    }

                    $handled[$key] = true;
                    $insert[] = [
                        'employee_id'     => $row['employee_id'],
                        'punched_at'      => $row['at']->format('Y-m-d H:i:s'),
                        'source'          => PunchSource::Device->value,
                        'direction'       => $row['direction']?->value,
                        'device_ref'      => $row['device_ref'],
                        'import_batch_id' => $batch->id,
                        'ip'              => null,
                        'latitude'        => null,
                        'longitude'       => null,
                        'creator_id'      => $by->id,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];

                    $localMoment = $recorder->localMoment($employees->get($row['employee_id']), $row['at']);
                    $affectedDays[$row['employee_id'].'|'.$localMoment->toDateString()] ??= [$row['employee_id'], $localMoment];
                }

                if ($insert === []) {
                    continue;
                }

                // Rows another request stored in the meantime are ignored by the unique index and counted as duplicates.
                $inserted = AttendancePunch::query()->insertOrIgnore($insert);

                $created += $inserted;
                $duplicates += count($insert) - $inserted;
            }

            foreach ($affectedDays as [$employeeId, $localMoment]) {
                $calculator->calculate($employees->get($employeeId), $localMoment);
            }

            $batch->update([
                'matched_rows'   => $created,
                'duplicate_rows' => $duplicates,
                'invalid_rows'   => $invalid,
            ]);

            return $batch;
        });
    }

    protected function tooManyRows(): AttendanceException
    {
        return new AttendanceException(__('attendances::attendances.import.errors.too_many_rows', ['max' => self::MAX_ROWS]));
    }

    /**
     * Employees keyed by their trimmed barcode (the device user id). Several employees under one key make
     * the id ambiguous. The comparison happens here, as case-sensitive text, because the database collation
     * would treat "AB1" and "ab1" as the same value.
     *
     * @return array<string, list<array{id: int}>>
     */
    protected function employeesByBarcode(): array
    {
        $map = [];

        Employee::query()
            ->whereNotNull('barcode')
            ->get(['id', 'barcode'])
            ->each(function (Employee $employee) use (&$map): void {
                $barcode = trim((string) $employee->barcode);

                if ($barcode !== '') {
                    $map[$barcode][] = ['id' => $employee->id];
                }
            });

        return $map;
    }

    /**
     * @param  list<array{employee_id: int, at: CarbonImmutable, direction: ?PunchDirection, device_ref: string}>  $rows
     * @return array<string, true> keyed `employee_id|Y-m-d H:i:s` (UTC) for device punches already stored
     */
    protected function storedDevicePunches(array $rows): array
    {
        $stored = [];

        // One query per chunk: the employee and instant lists give a superset of pairs, and the exact
        // pairs are told apart by the returned keys.
        foreach (array_chunk($rows, self::LOOKUP_CHUNK) as $chunk) {
            AttendancePunch::query()
                ->toBase()
                ->where('source', PunchSource::Device->value)
                ->whereIn('employee_id', array_unique(array_column($chunk, 'employee_id')))
                ->whereIn('punched_at', array_unique(array_map(fn (array $row): string => $row['at']->format('Y-m-d H:i:s'), $chunk)))
                ->get(['employee_id', 'punched_at'])
                ->each(function (object $punch) use (&$stored): void {
                    $stored[$punch->employee_id.'|'.$punch->punched_at] = true;
                });
        }

        return $stored;
    }

    /**
     * The instant in UTC, or null when the cell is blank or not a recognisable date and time.
     */
    protected function parseTimestamp(mixed $value, string $zone): ?CarbonImmutable
    {
        try {
            if (is_int($value) || is_float($value)) {
                if ($value <= self::EXCEL_SERIAL_THRESHOLD) {
                    return null;
                }

                $moment = CarbonImmutable::instance(SpreadsheetDate::excelToDateTimeObject($value, $zone));
            } elseif ($value instanceof DateTimeInterface) {
                $moment = CarbonImmutable::instance($value);
            } elseif (is_string($value)) {
                $value = trim($value);

                // Day first, as device exports in this region write it: 02/11/2026 is 2 November.
                $value = preg_replace_callback(
                    '#^(\d{1,2})/(\d{1,2})/(\d{4})(?=$|[\sT])#',
                    fn (array $m): string => sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]),
                    $value,
                ) ?? $value;

                if (! preg_match('#\d{4}-\d{1,2}-\d{1,2}|\d{1,2}[-.]\d{1,2}[-.]\d{4}#', $value)) {
                    return null;
                }

                $moment = CarbonImmutable::parse($value, $zone);
            } else {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        if ($moment->year < 1990 || $moment->year > 2100) {
            return null;
        }

        return $moment->utc()->startOfSecond();
    }

    protected function parseDirection(mixed $value): ?PunchDirection
    {
        return match (strtolower($this->text($value))) {
            'in', '1'  => PunchDirection::In,
            'out', '0' => PunchDirection::Out,
            default    => null,
        };
    }

    /**
     * A cell as trimmed text; whole numbers read from a spreadsheet as 1001.0 become "1001".
     */
    protected function text(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            return (string) (int) $value;
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @param  array<int, mixed>  $row
     */
    protected function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Device exports use a comma, a semicolon or a tab; pick the one the first line uses most.
     */
    protected function detectDelimiter(string $path): string
    {
        $firstLine = (string) (@file($path, FILE_IGNORE_NEW_LINES)[0] ?? '');
        $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);

        $best = array_key_first($counts);

        return $counts[$best] > 0 ? $best : ',';
    }

    protected function importable(?string $delimiter): object
    {
        return new class($delimiter) implements WithCustomCsvSettings
        {
            public function __construct(private readonly ?string $delimiter) {}

            /**
             * @return array<string, mixed>
             */
            public function getCsvSettings(): array
            {
                return $this->delimiter === null ? [] : ['delimiter' => $this->delimiter];
            }
        };
    }
}
