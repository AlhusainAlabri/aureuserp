<?php

namespace App\Support;

use App\Filament\Assets\Pages\AssetsDashboard;
use App\Filament\Inventory\Pages\InventoryDashboard;
use App\Filament\Pages\Dashboard as OrgDashboard;
use App\Filament\Projects\Pages\TaskOperationsHub;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Webkul\Correspondence\Filament\Pages\CorrespondenceDashboard;
use Webkul\DocumentArchive\Filament\Pages\DocumentDashboard;
use Webkul\Meetings\Filament\Pages\MeetingDashboard;
use Webkul\Project\Filament\Pages\Dashboard;
use Webkul\Recruitment\Filament\Pages\Recruitments;

class DashboardRegistry
{
    /**
     * @return array<int, array{label: string, description: string, url: string, icon: string, color: string}>
     */
    public static function links(): array
    {
        $links = [
            self::entry(
                label: __('dashboard.hub.org'),
                description: __('dashboard.hub.org_description'),
                pageClass: OrgDashboard::class,
                icon: 'heroicon-o-home',
                color: 'primary',
            ),
            self::entry(
                label: __('dashboard.hub.projects'),
                description: __('dashboard.hub.projects_description'),
                pageClass: Dashboard::class,
                icon: 'heroicon-o-folder',
                color: 'info',
            ),
            self::entry(
                label: __('dashboard.hub.tasks'),
                description: __('dashboard.hub.tasks_description'),
                pageClass: TaskOperationsHub::class,
                icon: 'heroicon-o-clipboard-document-check',
                color: 'warning',
            ),
            self::entry(
                label: __('dashboard.hub.meetings'),
                description: __('dashboard.hub.meetings_description'),
                pageClass: MeetingDashboard::class,
                icon: 'heroicon-o-calendar-days',
                color: 'success',
                table: 'meetings',
            ),
            self::entry(
                label: __('dashboard.hub.correspondence'),
                description: __('dashboard.hub.correspondence_description'),
                pageClass: CorrespondenceDashboard::class,
                icon: 'heroicon-o-envelope',
                color: 'danger',
                table: 'correspondences',
            ),
            self::entry(
                label: __('dashboard.hub.inventory'),
                description: __('dashboard.hub.inventory_description'),
                pageClass: InventoryDashboard::class,
                icon: 'heroicon-o-building-storefront',
                color: 'info',
                table: 'inventories_order_points',
            ),
            self::entry(
                label: __('dashboard.hub.assets'),
                description: __('dashboard.hub.assets_description'),
                pageClass: AssetsDashboard::class,
                icon: 'heroicon-o-cube',
                color: 'warning',
                table: 'assets',
            ),
            self::entry(
                label: __('dashboard.hub.documents'),
                description: __('dashboard.hub.documents_description'),
                pageClass: DocumentDashboard::class,
                icon: 'heroicon-o-archive-box',
                color: 'gray',
                table: 'doc_files',
            ),
            self::entry(
                label: __('dashboard.hub.recruitment'),
                description: __('dashboard.hub.recruitment_description'),
                pageClass: Recruitments::class,
                icon: 'heroicon-o-user-plus',
                color: 'success',
            ),
            self::entry(
                label: __('dashboard.hub.time_off'),
                description: __('dashboard.hub.time_off_description'),
                pageClass: \Webkul\TimeOff\Filament\Pages\Dashboard::class,
                icon: 'heroicon-o-clock',
                color: 'info',
            ),
        ];

        return array_values(array_filter($links));
    }

    /**
     * @return array{label: string, description: string, url: string, icon: string, color: string}|null
     */
    protected static function entry(
        string $label,
        string $description,
        string $pageClass,
        string $icon,
        string $color,
        ?string $table = null,
    ): ?array {
        if (! class_exists($pageClass)) {
            return null;
        }

        if ($table !== null && ! Schema::hasTable($table)) {
            return null;
        }

        if (! is_subclass_of($pageClass, Page::class)) {
            return null;
        }

        if (! $pageClass::canAccess()) {
            return null;
        }

        return [
            'label'       => $label,
            'description' => $description,
            'url'         => FilamentUrl::appendLocaleToUrl($pageClass::getUrl()),
            'icon'        => $icon,
            'color'       => $color,
        ];
    }
}
