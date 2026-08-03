<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Webkul\DocumentArchive\Filament\Pages\DocumentManager;
use Webkul\DocumentArchive\Support\DocumentDashboardMetrics;
use Webkul\DocumentArchive\Support\FilamentUrl;

class DocumentStatsWidget extends BaseWidget
{
    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected ?string $managerUrl = null;

    protected ?string $expiringUrl = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $expiringSoonDays = (int) config('document-archive.expiring_soon_days', 7);
        $metrics = DocumentDashboardMetrics::forCurrentUser();

        $managerUrl = $this->managerUrl();
        $expiringUrl = $this->expiringUrl();

        return [
            Stat::make(
                __('document-archive::document-archive.dashboard.stats.total_files'),
                $metrics['total_files'],
            )
                ->descriptionIcon('heroicon-o-document')
                ->color('primary')
                ->url($managerUrl),

            Stat::make(
                __('document-archive::document-archive.dashboard.stats.total_storage'),
                $this->formatBytes($metrics['total_bytes']),
            )
                ->descriptionIcon('heroicon-o-circle-stack')
                ->color('info')
                ->url($managerUrl),

            Stat::make(
                __('document-archive::document-archive.dashboard.stats.expiring_soon'),
                $metrics['expiring_soon'],
            )
                ->description(__('document-archive::document-archive.dashboard.stats.expires_within_days', [
                    'days' => $expiringSoonDays,
                ]))
                ->descriptionIcon('heroicon-o-clock')
                ->color($metrics['expiring_soon'] > 0 ? 'warning' : 'gray')
                ->url($expiringUrl),
        ];
    }

    protected function managerUrl(): string
    {
        if ($this->managerUrl !== null) {
            return $this->managerUrl;
        }

        $url = DocumentManager::getUrl(FilamentUrl::withLocale());

        return $this->managerUrl = class_exists(FilamentUrl::class)
            ? FilamentUrl::appendLocaleToUrl($url)
            : $url;
    }

    protected function expiringUrl(): string
    {
        if ($this->expiringUrl !== null) {
            return $this->expiringUrl;
        }

        $url = DocumentManager::getUrl(FilamentUrl::withLocale(['filter' => 'expiring']));

        return $this->expiringUrl = class_exists(FilamentUrl::class)
            ? FilamentUrl::appendLocaleToUrl($url)
            : $url;
    }

    protected function formatBytes(int $bytes): string
    {
        return Number::fileSize($bytes);
    }
}
