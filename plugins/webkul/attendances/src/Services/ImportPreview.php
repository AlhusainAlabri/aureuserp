<?php

namespace Webkul\Attendance\Services;

use Carbon\CarbonImmutable;
use Webkul\Attendance\Enums\PunchDirection;

/**
 * What an import would do, computed before anything is written. `matched` holds only the punches that
 * would be created (repeated rows and punches already stored are counted in `duplicates` instead).
 */
final readonly class ImportPreview
{
    /**
     * @param  list<array{employee_id: int, at: CarbonImmutable, direction: ?PunchDirection, device_ref: string}>  $matched
     * @param  list<string>  $unmatchedDeviceIds  Device ids that no employee carries as a barcode.
     * @param  list<string>  $ambiguousDeviceIds  Device ids carried by several employees; never guessed.
     * @param  int  $totalRows  Data rows read, not counting the header and fully blank lines.
     */
    public function __construct(
        public array $matched = [],
        public array $unmatchedDeviceIds = [],
        public int $duplicates = 0,
        public int $invalidRows = 0,
        public array $ambiguousDeviceIds = [],
        public int $totalRows = 0,
    ) {}
}
