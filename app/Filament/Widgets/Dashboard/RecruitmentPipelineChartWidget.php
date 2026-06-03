<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecruitmentPipelineChartWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 23;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.charts.recruitment_pipeline');
    }

    protected function getData(): array
    {
        if (! Schema::hasTable('recruitments_applicants') || ! Schema::hasTable('recruitments_stages')) {
            return $this->emptyPluginData();
        }

        $rows = DB::table('recruitments_applicants')
            ->join('recruitments_stages', 'recruitments_applicants.stage_id', '=', 'recruitments_stages.id')
            ->selectRaw('recruitments_stages.name as stage_name, COUNT(*) as total')
            ->where('recruitments_applicants.is_active', true)
            ->whereNull('recruitments_applicants.date_closed')
            ->groupBy('recruitments_stages.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        if ($rows->isEmpty()) {
            return $this->emptyPluginData();
        }

        $colors = ['#3B82F6', '#22C55E', '#F97316', '#A855F7', '#EC4899', '#14B8A6', '#EAB308', '#6B7280'];

        return [
            'datasets' => [[
                'data'            => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
                'backgroundColor' => array_slice($colors, 0, $rows->count()),
            ]],
            'labels' => $rows->pluck('stage_name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    protected function emptyPluginData(): array
    {
        return [
            'datasets' => [[
                'data'            => [1],
                'backgroundColor' => ['#9CA3AF'],
            ]],
            'labels' => [__('dashboard.plugin_not_installed')],
        ];
    }
}
