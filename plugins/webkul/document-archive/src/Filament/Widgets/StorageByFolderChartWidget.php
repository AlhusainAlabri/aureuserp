<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Services\DocumentAccessService;

class StorageByFolderChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

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
        return [
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
    }

    protected function getData(): array
    {
        $folders = $this->accessibleFilesQuery()
            ->reorder()
            ->select('folder_id', DB::raw('SUM(file_size) as total_bytes'))
            ->whereNotNull('folder_id')
            ->groupBy('folder_id')
            ->orderByDesc('total_bytes')
            ->limit(8)
            ->get();

        if ($folders->isEmpty()) {
            return [
                'datasets' => [],
                'labels'   => [],
            ];
        }

        $folderNames = DocFolder::query()
            ->whereIn('id', $folders->pluck('folder_id'))
            ->pluck('name', 'id');

        $labels = $folders->map(function ($row) use ($folderNames): string {
            $name = $folderNames[$row->folder_id] ?? __('document-archive::document-archive.manager.root');

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

    public function getDescription(): string|Htmlable|null
    {
        $folders = $this->accessibleFilesQuery()
            ->reorder()
            ->select('folder_id', DB::raw('SUM(file_size) as total_bytes'))
            ->whereNotNull('folder_id')
            ->groupBy('folder_id')
            ->orderByDesc('total_bytes')
            ->limit(1)
            ->get();

        if ($folders->isEmpty()) {
            return __('document-archive::document-archive.dashboard.charts.empty');
        }

        $largest = (int) $folders->first()->total_bytes;

        return __('document-archive::document-archive.dashboard.charts.largest_folder', [
            'size' => Number::fileSize($largest),
        ]);
    }

    protected function accessibleFilesQuery(): Builder
    {
        $query = DocFile::query();

        app(DocumentAccessService::class)->applyAccessibleFilesScope($query);

        return $query;
    }
}
