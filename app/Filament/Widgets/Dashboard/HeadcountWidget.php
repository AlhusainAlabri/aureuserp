<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HeadcountWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 12;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.widgets.headcount');
    }

    protected function getData(): array
    {
        try {
            if (! Schema::hasTable('employees_employees')) {
                return [
                    'datasets' => [[
                        'data'            => [1],
                        'backgroundColor' => ['#9CA3AF'],
                    ]],
                    'labels' => [__('dashboard.plugin_not_installed')],
                ];
            }

            $colors = [
                '#3B82F6', '#22C55E', '#F97316', '#A855F7',
                '#EC4899', '#14B8A6', '#EAB308', '#6B7280',
            ];

            $data = DB::table('employees_employees')
                ->join('employees_departments', 'employees_employees.department_id', '=', 'employees_departments.id')
                ->selectRaw('employees_departments.name as dept, COUNT(*) as total')
                ->where('employees_employees.active', true)
                ->groupBy('employees_departments.name')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(8)
                ->get();

            return [
                'datasets' => [[
                    'data'            => $data->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                ]],
                'labels' => $data->pluck('dept')->toArray(),
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
