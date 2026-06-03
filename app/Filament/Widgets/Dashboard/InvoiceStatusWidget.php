<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvoiceStatusWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 6;

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return __('dashboard.widgets.invoice_status');
    }

    protected function getData(): array
    {
        try {
            if (! Schema::hasTable('accounts_moves')) {
                return [
                    'datasets' => [[
                        'data'            => [1],
                        'backgroundColor' => ['#9CA3AF'],
                    ]],
                    'labels' => [__('dashboard.plugin_not_installed')],
                ];
            }

            $invoices = DB::table('accounts_moves')
                ->where('move_type', 'out_invoice')
                ->selectRaw("
                    SUM(CASE WHEN payment_state = 'paid' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN payment_state = 'not_paid' AND due_date >= CURDATE() THEN 1 ELSE 0 END) as unpaid,
                    SUM(CASE WHEN payment_state = 'not_paid' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN state = 'draft' THEN 1 ELSE 0 END) as draft
                ")
                ->first();

            return [
                'datasets' => [[
                    'data' => [
                        $invoices->paid ?? 0,
                        $invoices->unpaid ?? 0,
                        $invoices->overdue ?? 0,
                        $invoices->draft ?? 0,
                    ],
                    'backgroundColor' => ['#22C55E', '#EF4444', '#F97316', '#9CA3AF'],
                    'borderWidth'     => 2,
                ]],
                'labels' => [
                    __('dashboard.chart.paid'),
                    __('dashboard.chart.unpaid'),
                    __('dashboard.chart.overdue'),
                    __('dashboard.chart.draft'),
                ],
            ];
        } catch (\Exception) {
            return ['datasets' => [], 'labels' => []];
        }
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
