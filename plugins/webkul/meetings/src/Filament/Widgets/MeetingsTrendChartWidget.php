<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Filament\Widgets\Concerns\InteractsWithMeetingDashboardFilters;

class MeetingsTrendChartWidget extends ChartWidget
{
    use HasMeetingVisibility;
    use InteractsWithMeetingDashboardFilters;
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 7;

    public ?string $filter = 'year';

    public function getHeading(): ?string
    {
        return __('meetings::meetings.dashboard.charts.meetings_trend');
    }

    public function getDescription(): ?string
    {
        return __('meetings::meetings.dashboard.charts.meetings_trend_description');
    }

    protected function getFilters(): ?array
    {
        return [
            'year'          => __('meetings::meetings.dashboard.charts.filter_year'),
            'last_6_months' => __('meetings::meetings.dashboard.charts.filter_last_6_months'),
        ];
    }

    protected function getData(): array
    {
        [$start, $end] = $this->chartRange();

        $trend = Trend::query($this->filteredMeetingsQuery())
            ->between(start: $start, end: $end)
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label'           => __('meetings::meetings.dashboard.charts.meetings_trend'),
                    'data'            => $trend->map(fn (TrendValue $value): int => (int) $value->aggregate)->toArray(),
                    'borderColor'     => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'fill'            => true,
                    'tension'         => 0.35,
                ],
            ],
            'labels' => $trend->map(fn (TrendValue $value): string => Carbon::parse($value->date)->translatedFormat('M Y'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function chartRange(): array
    {
        if ($this->filter === 'last_6_months') {
            return [now()->subMonths(5)->startOfMonth(), now()->endOfMonth()];
        }

        $filters = $this->meetingDashboardFilters();
        $startDate = filled($filters['startDate'] ?? null)
            ? Carbon::parse($filters['startDate'])->startOfDay()
            : now()->startOfYear();
        $endDate = filled($filters['endDate'] ?? null)
            ? Carbon::parse($filters['endDate'])->endOfDay()
            : now()->endOfYear();

        return [$startDate, $endDate];
    }
}
