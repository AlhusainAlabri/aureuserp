<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Filament\Widgets\Concerns\InteractsWithMeetingDashboardFilters;

class MeetingsStatusChartWidget extends ChartWidget
{
    use HasMeetingVisibility;
    use InteractsWithMeetingDashboardFilters;
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 5;

    public function getHeading(): ?string
    {
        return __('meetings::meetings.dashboard.charts.meetings_status');
    }

    public function getDescription(): ?string
    {
        return __('meetings::meetings.dashboard.charts.meetings_status_description');
    }

    protected function getData(): array
    {
        $statusOptions = MeetingResource::statusOptions();
        $counts = $this->filteredMeetingsQuery()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($statusOptions as $status => $label) {
            $labels[] = $label;
            $data[] = (int) ($counts[$status] ?? 0);
            $colors[] = $this->statusColor($status);
        }

        return [
            'datasets' => [
                [
                    'label'           => __('meetings::meetings.dashboard.charts.meetings_status'),
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderWidth'     => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function statusColor(string $status): string
    {
        return match ($status) {
            'pending_approval' => '#F59E0B',
            'approved'         => '#3B82F6',
            'confirmed'        => '#10B981',
            'archived'         => '#6B7280',
            default            => '#9CA3AF',
        };
    }
}
