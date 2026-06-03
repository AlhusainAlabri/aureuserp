<?php

namespace App\Filament\Widgets\Employee;

use App\Models\Hr\EmployeeSalaryRaise;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Schema;

class EmployeeSalaryHistoryChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;

    public ?int $employeeId = null;

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return __('hr-extensions::salary_raise.chart_heading');
    }

    public function getDescription(): ?string
    {
        if ($this->getData()['datasets'] === []) {
            return __('hr-extensions::salary_raise.chart_empty');
        }

        return null;
    }

    protected function getData(): array
    {
        if (! $this->employeeId || ! Schema::hasTable('employee_salary_raises')) {
            return ['datasets' => [], 'labels' => []];
        }

        $raises = EmployeeSalaryRaise::query()
            ->where('employee_id', $this->employeeId)
            ->orderBy('effective_date')
            ->get();

        if ($raises->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }

        $labels = [];
        $amounts = [];
        $raisePercents = [];

        foreach ($raises as $raise) {
            if ($labels === []) {
                $labels[] = $raise->effective_date->format('M Y');
                $amounts[] = (float) $raise->old_amount;
                $raisePercents[] = null;
            }

            $labels[] = $raise->effective_date->format('M Y');
            $amounts[] = (float) $raise->new_amount;
            $raisePercents[] = (float) $raise->raise_percent;
        }

        return [
            'datasets' => [
                [
                    'label'           => __('hr-extensions::salary_raise.fields.new_amount'),
                    'data'            => $amounts,
                    'stepped'         => true,
                    'borderColor'     => 'rgb(20, 184, 166)',
                    'backgroundColor' => 'rgba(20, 184, 166, 0.15)',
                    'borderWidth'     => 2,
                    'fill'            => true,
                    'raisePercents'   => $raisePercents,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            const dataset = context.dataset;
                            const percent = dataset.raisePercents?.[context.dataIndex];
                            const value = context.parsed.y;
                            let label = 'OMR ' + value.toFixed(3);
                            if (percent !== null && percent !== undefined) {
                                label += ' (+' + percent.toFixed(2) + '%)';
                            }
                            return label;
                        }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'ticks' => [
                        'callback' => "function(value) { return 'OMR ' + value.toFixed(3); }",
                    ],
                ],
            ],
        ];
    }
}
