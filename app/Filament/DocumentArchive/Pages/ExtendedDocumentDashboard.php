<?php

namespace App\Filament\DocumentArchive\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use App\Filament\DocumentArchive\Widgets\DocumentStatsWidget;
use Webkul\DocumentArchive\Filament\Pages\DocumentDashboard as BaseDocumentDashboard;
use Webkul\DocumentArchive\Filament\Widgets\ExpiringSoonFilesWidget;
use Webkul\DocumentArchive\Filament\Widgets\RecentFilesWidget;
use Webkul\DocumentArchive\Filament\Widgets\StorageByFolderChartWidget;
use Webkul\DocumentArchive\Filament\Widgets\TopTagsChartWidget;

class ExtendedDocumentDashboard extends BaseDocumentDashboard
{
    use InteractsWithAdvancedDashboard;

    protected string $view = 'filament.pages.advanced-dashboard';

    public function getTitle(): string
    {
        return __('dashboard.hub.documents');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.hub.documents_description');
    }

    public function getHeaderWidgets(): array
    {
        return [
            DocumentStatsWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        $widgets = [
            TopTagsChartWidget::class,
            StorageByFolderChartWidget::class,
        ];

        if (request('filter') === 'expiring') {
            $widgets[] = ExpiringSoonFilesWidget::class;
        }

        $widgets[] = RecentFilesWidget::class;

        return $widgets;
    }
}
