<?php

namespace Webkul\Payroll\Services;

use App\Support\Exports\Utf8Csv;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Models\Payslip;

class PasiScheduleBuilder
{
    public function __construct(
        protected PasiRuleResolver $resolver,
        protected PasiCalculator $calculator,
    ) {}

    /**
     * One row per employee with PASI on a validated or paid payslip of the month.
     *
     * @return Collection<int, array{employee: string, civil_id: ?string, category: string, insurable_salary: float, employee_share: float, employer_share: float}>
     */
    public function rows(int $year, int $month): Collection
    {
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        return Payslip::query()
            ->whereIn('status', [PayslipStatus::Validated->value, PayslipStatus::Paid->value])
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->with(['employee.company', 'lines.component'])
            ->get()
            ->map(function (Payslip $payslip) use ($periodEnd): ?array {
                $employeeShare = $this->shareOf($payslip, CalculationType::PasiEmployee);
                $employerShare = $this->shareOf($payslip, CalculationType::PasiEmployer);

                if ($employeeShare == 0.0 && $employerShare == 0.0) {
                    return null;
                }

                $category = $this->resolver->categoryFor($payslip->employee);
                $rule = $this->resolver->ruleFor($payslip->employee, $periodEnd);

                return [
                    'employee'         => (string) $payslip->employee->name,
                    'civil_id'         => $payslip->employee->civil_id,
                    'category'         => $category?->getLabel() ?? '-',
                    'insurable_salary' => $rule === null ? 0.0 : $this->calculator->base($payslip, $rule),
                    'employee_share'   => $employeeShare,
                    'employer_share'   => $employerShare,
                ];
            })
            ->filter()
            ->sortBy('employee')
            ->values();
    }

    /**
     * @param  Collection<int, array{insurable_salary: float, employee_share: float, employer_share: float}>  $rows
     * @return array{insurable_salary: float, employee_share: float, employer_share: float}
     */
    public function totals(Collection $rows): array
    {
        return [
            'insurable_salary' => round((float) $rows->sum('insurable_salary'), 3),
            'employee_share'   => round((float) $rows->sum('employee_share'), 3),
            'employer_share'   => round((float) $rows->sum('employer_share'), 3),
        ];
    }

    public function csv(int $year, int $month): string
    {
        $header = [
            __('payroll::payroll.pasi.schedule.columns.employee'),
            __('payroll::payroll.pasi.schedule.columns.civil_id'),
            __('payroll::payroll.pasi.schedule.columns.category'),
            __('payroll::payroll.pasi.schedule.columns.insurable_salary'),
            __('payroll::payroll.pasi.schedule.columns.employee_share'),
            __('payroll::payroll.pasi.schedule.columns.employer_share'),
        ];

        $lines = $this->rows($year, $month)->map(fn (array $row): array => [
            $row['employee'],
            $row['civil_id'],
            $row['category'],
            number_format($row['insurable_salary'], 3, '.', ''),
            number_format($row['employee_share'], 3, '.', ''),
            number_format($row['employer_share'], 3, '.', ''),
        ])->all();

        return Utf8Csv::fromRows([$header, ...$lines]);
    }

    protected function shareOf(Payslip $payslip, CalculationType $type): float
    {
        return round((float) $payslip->lines
            ->filter(fn ($line): bool => $line->component?->calculation_type === $type)
            ->sum('amount'), 3);
    }
}
