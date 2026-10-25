<?php

namespace Tests\Helpers;

use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Services\AttendanceBridge;

/**
 * Pins payroll to "attendance plugin not installed": the test database has the attendances tables and a
 * company-less default calendar every employee falls back to, so tests describing the legacy 22-day month
 * must switch the bridge off explicitly.
 */
class PayrollWithoutAttendance
{
    public static function use(): void
    {
        app()->instance(AttendanceBridge::class, new class extends AttendanceBridge
        {
            public function available(): bool
            {
                return false;
            }

            public function summary(Employee $employee, int $year, int $month): ?array
            {
                return null;
            }
        });
    }

    public static function restore(): void
    {
        app()->forgetInstance(AttendanceBridge::class);
    }
}
