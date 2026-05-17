<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\DocumentArchive\Models\DocFile;

class DocumentStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $totalFiles = DocFile::query()->count();
        $totalBytes = (int) DocFile::query()->sum('file_size');
        $expiringSoon = DocFile::query()->expiringSoon(7)->count();

        return [
            Stat::make(
                __('document-archive::document-archive.dashboard.stats.total_files'),
                $totalFiles
            )
                ->descriptionIcon('heroicon-o-document')
                ->color('primary'),

            Stat::make(
                __('document-archive::document-archive.dashboard.stats.total_storage'),
                $this->formatBytes($totalBytes)
            )
                ->descriptionIcon('heroicon-o-circle-stack')
                ->color('info'),

            Stat::make(
                __('document-archive::document-archive.dashboard.stats.expiring_soon'),
                $expiringSoon
            )
                ->descriptionIcon('heroicon-o-clock')
                ->color($expiringSoon > 0 ? 'warning' : 'gray'),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
