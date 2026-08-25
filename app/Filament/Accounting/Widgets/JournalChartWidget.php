<?php

namespace App\Filament\Accounting\Widgets;

use App\Support\OmrFormatter;
use Webkul\Accounting\Filament\Widgets\JournalChartWidget as BaseJournalChartWidget;

class JournalChartWidget extends BaseJournalChartWidget
{
    public function getDashboardData(): array
    {
        $data = parent::getDashboardData();

        $labelMap = [
            'To Validate' => 'accounting-extensions::pages.widget.to-validate',
            'Unpaid'      => 'accounting-extensions::pages.widget.unpaid',
            'Late'        => 'accounting-extensions::pages.widget.late',
            'To Pay'      => 'accounting-extensions::pages.widget.to-pay',
            'Entries'     => 'accounting-extensions::pages.widget.entries',
            'Payments'    => 'accounting-extensions::pages.widget.payments',
        ];

        foreach ($data['stats'] as &$stat) {
            if (isset($stat['label'], $labelMap[$stat['label']])) {
                $stat['label'] = __($labelMap[$stat['label']]);
            }

            if (isset($stat['amount'])) {
                $stat['formatted_amount'] = OmrFormatter::format($stat['amount']);
            }
        }
        unset($stat);

        foreach ($data['actions'] as &$action) {
            $action['label'] = match ($action['label']) {
                'New Entry'   => __('accounting-extensions::pages.widget.new-entry'),
                'New Invoice' => __('accounting-extensions::pages.widget.new-invoice'),
                'New Bill'    => __('accounting-extensions::pages.widget.new-bill'),
                'New Payment' => __('accounting-extensions::pages.widget.new-payment'),
                default       => $action['label'],
            };
        }
        unset($action);

        return $data;
    }

    public function getChartData(): array
    {
        $chartData = parent::getChartData();

        if (($chartData['type'] ?? '') === 'line') {
            $chartData['datasets'][0]['label'] = __('accounting-extensions::pages.widget.chart-balance');
        }

        if (($chartData['type'] ?? '') === 'bar') {
            $chartData['labels'] = collect($chartData['labels'])->map(function (string $label): string {
                return match ($label) {
                    'Overdue'   => __('accounting-extensions::pages.widget.chart-overdue'),
                    'This Week' => __('accounting-extensions::pages.widget.chart-this-week'),
                    'Not Due'   => __('accounting-extensions::pages.widget.chart-not-due'),
                    default     => $label,
                };
            })->all();

            foreach ($chartData['datasets'] as &$dataset) {
                $dataset['label'] = match ($dataset['label']) {
                    'Overdue' => __('accounting-extensions::pages.widget.chart-overdue'),
                    'On Time' => __('accounting-extensions::pages.widget.chart-on-time'),
                    default   => $dataset['label'],
                };
            }
            unset($dataset);
        }

        return $chartData;
    }

    public function render()
    {
        return view('accounting::filament.widgets.journal-chart-widget', [
            'dashboard' => $this->getDashboardData(),
        ]);
    }
}
