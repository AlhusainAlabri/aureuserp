<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Webkul\DocumentArchive\Filament\Widgets\Concerns\RendersEmptyDocumentChart;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Support\DocumentDashboardCache;

class StorageByFolderChartWidget extends ChartWidget
{
    use RendersEmptyDocumentChart;

    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg'      => 6,
    ];

    public function getHeading(): string|Htmlable|null
    {
        return __('document-archive::document-archive.dashboard.charts.storage_by_folder');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        $options = [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];

        if (! $this->hasChartData()) {
            return $this->applyEmptyChartOptions($options);
        }

        return $options;
    }

    protected function getData(): array
    {
        $folders = $this->getTopFoldersByStorage(limit: 8);

        if ($folders->isEmpty()) {
            return $this->emptyChartData();
        }

        $labels = $folders->map(function ($row): string {
            $name = $row->folder_name ?? __('document-archive::document-archive.manager.root');

            return strlen($name) > 24 ? substr($name, 0, 21).'...' : $name;
        })->all();

        $bytes = $folders->pluck('total_bytes')->map(fn ($value): int => (int) $value)->all();

        return [
            'datasets' => [
                [
                    'label'           => __('document-archive::document-archive.dashboard.charts.storage'),
                    'data'            => $bytes,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor'     => 'rgb(59, 130, 246)',
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function hasChartData(): bool
    {
        return $this->getTopFoldersByStorage(limit: 1)->isNotEmpty();
    }

    protected function getChartDescription(): ?string
    {
        $folders = $this->getTopFoldersByStorage(limit: 1);

        if ($folders->isEmpty()) {
            return null;
        }

        $largest = (int) $folders->first()->total_bytes;

        return __('document-archive::document-archive.dashboard.charts.largest_folder', [
            'size' => Number::fileSize($largest),
        ]);
    }

    protected function getTopFoldersByStorage(int $limit): Collection
    {
        $rows = once(fn (): array => DocumentDashboardCache::remember('storage_by_folder', function (): array {
            return $this->accessibleFilesQuery()
                ->reorder()
                ->join('doc_folders', 'doc_folders.id', '=', 'doc_files.folder_id')
                ->select('doc_files.folder_id')
                ->selectRaw('doc_folders.name as folder_name')
                ->selectRaw('SUM(doc_files.file_size) as total_bytes')
                ->groupBy('doc_files.folder_id', 'doc_folders.name')
                ->orderByDesc('total_bytes')
                ->limit(8)
                ->get()
                ->map(fn ($row): array => [
                    'folder_id'   => (int) $row->folder_id,
                    'folder_name' => (string) $row->folder_name,
                    'total_bytes' => (int) $row->total_bytes,
                ])
                ->all();
        }));

        return collect($rows)
            ->take($limit)
            ->map(fn (array $row): object => (object) $row)
            ->values();
    }

    protected function accessibleFilesQuery(): Builder
    {
        $query = DocFile::query();

        app(DocumentAccessService::class)->applyAccessibleFilesScope($query);

        return $query;
    }
}
