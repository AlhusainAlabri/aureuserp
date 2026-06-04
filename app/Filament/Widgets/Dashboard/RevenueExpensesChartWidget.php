<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use App\Support\Dashboard\DashboardMetricCache;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RevenueExpensesChartWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.widgets.revenue_expenses');
    }

    protected function getData(): array
    {
        return DashboardMetricCache::rememberWithFilters(
            'revenue_expenses_chart',
            $this->pageFilters ?? [],
            fn (): array => $this->buildChartData(),
        );
    }

    /**
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    protected function buildChartData(): array
    {
        try {
            $months = [];
            $revenue = [];
            $expenses = [];

            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $months[] = $month->translatedFormat('M Y');

                if (! Schema::hasTable('accounts_moves')) {
                    $revenue[] = 0;
                    $expenses[] = 0;

                    continue;
                }

                $revenue[] = (float) DB::table('accounts_moves')
                    ->where('move_type', 'out_invoice')
                    ->where('state', 'posted')
                    ->whereMonth('invoice_date', $month->month)
                    ->whereYear('invoice_date', $month->year)
                    ->sum('amount_total');

                $expenses[] = (float) DB::table('accounts_moves')
                    ->where('move_type', 'in_invoice')
                    ->where('state', 'posted')
                    ->whereMonth('invoice_date', $month->month)
                    ->whereYear('invoice_date', $month->year)
                    ->sum('amount_total');
            }

            return [
                'datasets' => [
                    [
                        'label'           => __('dashboard.chart.revenue'),
                        'data'            => $revenue,
                        'backgroundColor' => 'rgba(20, 184, 166, 0.7)',
                        'borderColor'     => 'rgb(20, 184, 166)',
                        'borderWidth'     => 2,
                    ],
                    [
                        'label'           => __('dashboard.chart.expenses'),
                        'data'            => $expenses,
                        'backgroundColor' => 'rgba(249, 115, 22, 0.7)',
                        'borderColor'     => 'rgb(249, 115, 22)',
                        'borderWidth'     => 2,
                    ],
                ],
                'labels' => $months,
            ];
        } catch (\Exception) {
            return ['datasets' => [], 'labels' => []];
        }
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
