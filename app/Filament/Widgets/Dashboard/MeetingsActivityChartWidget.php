<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardChartLayout;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Webkul\Meetings\Models\Meeting;

class MeetingsActivityChartWidget extends ChartWidget
{
    use HasOrgDashboardChartLayout;
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?int $sort = 22;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('dashboard.charts.meetings_activity');
    }

    protected function getData(): array
    {
        if (! Schema::hasTable('meetings') || ! class_exists(Meeting::class)) {
            return $this->emptyPluginData();
        }

        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $this->pageFilters['endDate'] ?? now()->format('Y-m-d');

        $query = Meeting::query()
            ->when(filled($startDate), fn ($q) => $q->whereDate('meeting_date', '>=', $startDate))
            ->when(filled($endDate), fn ($q) => $q->whereDate('meeting_date', '<=', $endDate));

        $trend = Trend::query($query)
            ->between(
                start: Carbon::parse($startDate)->startOfMonth(),
                end: Carbon::parse($endDate)->endOfMonth(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [[
                'label'           => __('dashboard.charts.meetings_count'),
                'data'            => $trend->map(fn (TrendValue $value): int => (int) $value->aggregate)->toArray(),
                'borderColor'     => '#3B82F6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                'fill'            => true,
                'tension'         => 0.35,
            ]],
            'labels' => $trend->map(fn (TrendValue $value): string => Carbon::parse($value->date)->translatedFormat('M Y'))->toArray(),
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
