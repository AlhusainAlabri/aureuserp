<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Webkul\DocumentArchive\Filament\Pages\DocumentDashboard;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Support\FilamentUrl;

class DocumentStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $expiringSoonDays = (int) config('document-archive.expiring_soon_days', 7);

        $totalFiles = DocFile::query()->count();
        $totalBytes = (int) DocFile::query()->sum('file_size');
        $expiringSoon = DocFile::query()->expiringSoon($expiringSoonDays)->count();

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
                ->description(__('document-archive::document-archive.dashboard.stats.expires_within_days', [
                    'days' => $expiringSoonDays,
                ]))
                ->descriptionIcon('heroicon-o-clock')
                ->color($expiringSoon > 0 ? 'warning' : 'gray')
                ->url(DocumentDashboard::getUrl(FilamentUrl::withLocale(['filter' => 'expiring']))),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        return Number::fileSize($bytes);
    }
}
