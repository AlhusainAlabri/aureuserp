<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentTagService;

class TopTagsChartWidget extends ChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string|Htmlable|null
    {
        return __('document-archive::document-archive.dashboard.charts.top_tags');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins'   => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks'       => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $tagCounts = app(DocumentTagService::class)->tagUsageCountsWithColors(
            $this->accessibleFilesQuery(),
            10,
        );

        if ($tagCounts === []) {
            return [
                'datasets' => [],
                'labels'   => [],
            ];
        }

        $labels = array_keys($tagCounts);
        $counts = array_column($tagCounts, 'count');
        $colors = array_column($tagCounts, 'color');

        return [
            'datasets' => [
                [
                    'label'           => __('document-archive::document-archive.dashboard.charts.files_count'),
                    'data'            => $counts,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getDescription(): string|Htmlable|null
    {
        $tagCounts = app(DocumentTagService::class)->tagUsageCounts(
            $this->accessibleFilesQuery(),
            1,
        );

        if ($tagCounts === []) {
            return __('document-archive::document-archive.dashboard.charts.empty');
        }

        return null;
    }

    protected function accessibleFilesQuery(): Builder
    {
        $query = DocFile::query();

        app(DocumentAccessService::class)->applyAccessibleFilesScope($query);

        return $query;
    }
}
