<?php

namespace Webkul\DocumentArchive\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard as BaseDashboard;
use Webkul\DocumentArchive\Filament\Widgets\DocumentStatsWidget;
use Webkul\DocumentArchive\Filament\Widgets\ExpiringSoonFilesWidget;
use Webkul\DocumentArchive\Filament\Widgets\RecentFilesWidget;
use Webkul\DocumentArchive\Filament\Widgets\StorageByFolderChartWidget;
use Webkul\DocumentArchive\Filament\Widgets\TopTagsChartWidget;

class DocumentDashboard extends BaseDashboard
{
    use HasPageShield;

    protected static string $routePath = 'document-archive/dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 59;

    protected static function getPagePermission(): ?string
    {
        return 'view_any_document_archive_doc::file';
    }

    public static function getNavigationLabel(): string
    {
        return __('document-archive::document-archive.navigation.dashboard.label');
    }

    public function getTitle(): string
    {
        return __('document-archive::document-archive.dashboard.page_title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.document-archive');
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

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg'      => 2,
        ];
    }
}
