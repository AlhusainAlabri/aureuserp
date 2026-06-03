<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Schema;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;

class PurchasesSpendChartWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 26;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.charts.purchases_spend');
    }

    protected function getData(): array
    {
        $months = [];
        $amounts = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->translatedFormat('M Y');

            if (! Schema::hasTable('purchases_orders')) {
                $amounts[] = 0;

                continue;
            }

            $amounts[] = (float) Order::query()
                ->whereIn('state', [OrderState::PURCHASE->value, OrderState::DONE->value])
                ->whereMonth('approved_at', $date->month)
                ->whereYear('approved_at', $date->year)
                ->sum('total_amount');
        }

        return [
            'datasets' => [[
                'label'           => __('dashboard.chart.expenses'),
                'data'            => $amounts,
                'borderColor'     => 'rgb(249, 115, 22)',
                'backgroundColor' => 'rgba(249, 115, 22, 0.15)',
                'fill'            => true,
                'tension'         => 0.35,
            ]],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
