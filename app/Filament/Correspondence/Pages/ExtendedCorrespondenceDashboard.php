<?php

namespace App\Filament\Correspondence\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use Webkul\Correspondence\Filament\Pages\CorrespondenceDashboard as BaseCorrespondenceDashboard;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceApprovalsTable;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceDashboardStats;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceTasksTable;
use Webkul\Correspondence\Filament\Widgets\IncomingCorrespondencesTable;
use Webkul\Correspondence\Filament\Widgets\PendingOutgoingCorrespondencesTable;
use Webkul\Correspondence\Filament\Widgets\UrgentCorrespondencesTable;

class ExtendedCorrespondenceDashboard extends BaseCorrespondenceDashboard
{
    use InteractsWithAdvancedDashboard;

    protected string $view = 'filament.pages.advanced-dashboard';

    public function getTitle(): string
    {
        return __('dashboard.hub.correspondence');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.hub.correspondence_description');
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
