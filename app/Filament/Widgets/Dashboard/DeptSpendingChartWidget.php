<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Department;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;

class DeptSpendingChartWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 9;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.widgets.dept_spending');
    }

    protected function getData(): array
    {
        try {
            if (! Schema::hasTable('purchases_orders') || ! Schema::hasColumn('purchases_orders', 'requesting_department_id')) {
                return [
                    'datasets' => [[
                        'data'            => [1],
                        'backgroundColor' => ['#9CA3AF'],
                    ]],
                    'labels' => [__('dashboard.plugin_not_installed')],
                ];
            }

            $rows = Order::query()
                ->select('requesting_department_id', DB::raw('SUM(total_amount) as total'))
                ->whereIn('state', [OrderState::PURCHASE->value, OrderState::DONE->value])
                ->whereNotNull('requesting_department_id')
                ->groupBy('requesting_department_id')
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            $departments = Department::query()
                ->whereIn('id', $rows->pluck('requesting_department_id'))
                ->pluck('name', 'id');

            return [
                'datasets' => [[
                    'label'           => __('dashboard.chart.expenses'),
                    'data'            => $rows->pluck('total')->map(fn ($v) => (float) $v)->all(),
                    'backgroundColor' => '#3B82F6',
                ]],
                'labels' => $rows->map(fn ($row) => $departments[$row->requesting_department_id] ?? '#'.$row->requesting_department_id)->all(),
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
