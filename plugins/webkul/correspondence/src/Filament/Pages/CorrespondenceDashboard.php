<?php

namespace Webkul\Correspondence\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard as BaseDashboard;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceApprovalsTable;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceDashboardStats;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceTasksTable;
use Webkul\Correspondence\Filament\Widgets\IncomingCorrespondencesTable;
use Webkul\Correspondence\Filament\Widgets\PendingOutgoingCorrespondencesTable;
use Webkul\Correspondence\Filament\Widgets\UrgentCorrespondencesTable;

class CorrespondenceDashboard extends BaseDashboard
{
    use HasPageShield;

    protected static string $routePath = 'correspondence/dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 54;

    protected static function getPagePermission(): ?string
    {
        return 'view_any_correspondence_correspondence';
    }

    public static function getNavigationLabel(): string
    {
        return __('correspondence::correspondence.navigation.dashboard');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.correspondence');
    }

    public function getTitle(): string
    {
        return __('correspondence::correspondence.navigation.dashboard');
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'lg'      => 12,
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            CorrespondenceDashboardStats::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            IncomingCorrespondencesTable::class,
            PendingOutgoingCorrespondencesTable::class,
            CorrespondenceApprovalsTable::class,
            UrgentCorrespondencesTable::class,
            CorrespondenceTasksTable::class,
        ];
    }
}
