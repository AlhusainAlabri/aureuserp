<?php

namespace Webkul\Attendance\Services;

use App\Support\Exports\Utf8Csv;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

/**
 * The monthly attendance sheet: one row per employee the user may see, built from `AttendanceSummary`.
 * Employees without a work schedule have no figures (null) and are left out of the totals.
 */
class AttendanceMonthlyReport
{
    public const MAX_ROWS = 500;

    public function __construct(
        protected AttendanceSummary $summary,
        protected int $limit = self::MAX_ROWS,
    ) {}

    /**
     * @return array{
     *     rows: list<array{employee_id: int, employee: string, has_schedule: bool, working_days: ?int, absent_days: ?int, late_minutes: ?int, overtime_hours: ?float, overtime_by_multiplier: array<string, float>}>,
     *     totals: array{employees: int, working_days: int, absent_days: int, late_minutes: int, overtime_hours: float, overtime_by_multiplier: array<string, float>},
     *     truncated: bool,
     *     totalEmployees: int
     * }
     */
    public function build(User $user, int $year, int $month): array
    {
        $query = $this->employeesQuery($user, $year, $month);
        $employees = (clone $query)->limit($this->limit + 1)->get();
        $truncated = $employees->count() > $this->limit;

        $rows = $employees
            ->take($this->limit)
            ->map(fn (Employee $employee): array => $this->rowFor($employee, $year, $month))
            ->values()
            ->all();

        return [
            'rows'           => $rows,
            'totals'         => $this->totals($rows),
            'truncated'      => $truncated,
            'totalEmployees' => $truncated ? $query->count() : count($rows),
        ];
    }

    /**
     * Header plus one line per row; cells a spreadsheet would run as formulas are neutralised and the
     * figures of employees without a schedule are left empty.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function csv(array $rows): string
    {
        $lines = [[
            __('attendances::attendances.monthly.columns.employee'),
            __('attendances::attendances.monthly.columns.working_days'),
            __('attendances::attendances.monthly.columns.absent_days'),
            __('attendances::attendances.monthly.columns.late_minutes'),
            __('attendances::attendances.monthly.columns.overtime_hours'),
        ]];

        foreach ($rows as $row) {
            $lines[] = [
                $row['employee'],
                $row['working_days'],
                $row['absent_days'],
                $row['late_minutes'],
                $row['overtime_hours'] === null ? null : number_format($row['overtime_hours'], 2, '.', ''),
            ];
        }

        return Utf8Csv::fromRows(array_map(
            fn (array $line): array => array_map(AttendanceDayCsv::neutralise(...), $line),
            $lines,
        ));
    }

    /**
     * Active employees, plus anyone (also inactive or archived) with a day recorded in the month, sorted by name.
     *
     * @return Builder<Employee>
     */
    protected function employeesQuery(User $user, int $year, int $month): Builder
    {
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last = date('Y-m-t', (int) strtotime($first));

        $query = Employee::query()
            ->withTrashed()
            ->where(function (Builder $query) use ($first, $last): void {
                $query
                    ->where(function (Builder $active): void {
                        $active->where('is_active', true)->whereNull('deleted_at');
                    })
                    ->orWhereIn('id', AttendanceDay::query()->whereBetween('date', [$first, $last])->select('employee_id'));
            })
            ->orderBy('name')
            ->orderBy('id');

        $ids = AttendanceScope::employeeIdsFor($user);

        return $ids === null ? $query : $query->whereIn('id', $ids->all());
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowFor(Employee $employee, int $year, int $month): array
    {
        $summary = $this->summary->forMonth($employee, $year, $month);

        return [
            'employee_id'            => (int) $employee->id,
            'employee'               => (string) $employee->name,
            'has_schedule'           => $summary->hasSchedule,
            'working_days'           => $summary->hasSchedule ? $summary->workingDays : null,
            'absent_days'            => $summary->hasSchedule ? $summary->absentDays : null,
            'late_minutes'           => $summary->hasSchedule ? $summary->lateMinutes : null,
            'overtime_hours'         => $summary->hasSchedule ? round(array_sum($summary->overtimeHours), 2) : null,
            'overtime_by_multiplier' => $summary->hasSchedule ? $summary->overtimeHours : [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{employees: int, working_days: int, absent_days: int, late_minutes: int, overtime_hours: float, overtime_by_multiplier: array<string, float>}
     */
    protected function totals(array $rows): array
    {
        $scheduled = array_values(array_filter($rows, fn (array $row): bool => $row['has_schedule']));
        $byMultiplier = [];

        foreach ($scheduled as $row) {
            foreach ($row['overtime_by_multiplier'] as $multiplier => $hours) {
                $byMultiplier[$multiplier] = ($byMultiplier[$multiplier] ?? 0.0) + $hours;
            }
        }

        ksort($byMultiplier, SORT_NUMERIC);

        return [
            'employees'              => count($scheduled),
            'working_days'           => (int) array_sum(array_column($scheduled, 'working_days')),
            'absent_days'            => (int) array_sum(array_column($scheduled, 'absent_days')),
            'late_minutes'           => (int) array_sum(array_column($scheduled, 'late_minutes')),
            'overtime_hours'         => round(array_sum(array_column($scheduled, 'overtime_hours')), 2),
            'overtime_by_multiplier' => array_map(fn (float $hours): float => round($hours, 2), $byMultiplier),
        ];
    }
}
