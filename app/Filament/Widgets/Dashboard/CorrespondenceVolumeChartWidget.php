<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Webkul\Correspondence\Models\Correspondence;

class CorrespondenceVolumeChartWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 24;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.charts.correspondence_volume');
    }

    protected function getData(): array
    {
        if (! Schema::hasTable('correspondences') || ! class_exists(Correspondence::class)) {
            return $this->emptyPluginData();
        }

        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $this->pageFilters['endDate'] ?? now()->format('Y-m-d');

        $incoming = Trend::query(
            Correspondence::query()
                ->where('direction', 'incoming')
                ->when(filled($startDate), fn ($q) => $q->whereDate('received_at', '>=', $startDate))
                ->when(filled($endDate), fn ($q) => $q->whereDate('received_at', '<=', $endDate)),
        )
            ->between(
                start: Carbon::parse($startDate)->startOfMonth(),
                end: Carbon::parse($endDate)->endOfMonth(),
            )
            ->perMonth()
            ->count();

        $outgoing = Trend::query(
            Correspondence::query()
                ->where('direction', 'outgoing')
                ->when(filled($startDate), fn ($q) => $q->whereDate('received_at', '>=', $startDate))
                ->when(filled($endDate), fn ($q) => $q->whereDate('received_at', '<=', $endDate)),
        )
            ->between(
                start: Carbon::parse($startDate)->startOfMonth(),
                end: Carbon::parse($endDate)->endOfMonth(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label'           => __('dashboard.charts.correspondence_incoming'),
                    'data'            => $incoming->map(fn (TrendValue $value): int => (int) $value->aggregate)->toArray(),
                    'borderColor'     => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.35,
                ],
                [
                    'label'           => __('dashboard.charts.correspondence_outgoing'),
                    'data'            => $outgoing->map(fn (TrendValue $value): int => (int) $value->aggregate)->toArray(),
                    'borderColor'     => '#22C55E',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.35,
                ],
            ],
            'labels' => $incoming->map(fn (TrendValue $value): string => Carbon::parse($value->date)->translatedFormat('M Y'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    protected function emptyPluginData(): array
    {
        return [
            'datasets' => [[
                'data'            => [0],
                'backgroundColor' => ['#9CA3AF'],
            ]],
            'labels' => [__('dashboard.plugin_not_installed')],
        ];
    }
}
