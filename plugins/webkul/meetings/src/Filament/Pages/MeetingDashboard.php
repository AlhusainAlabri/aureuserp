<?php

namespace Webkul\Meetings\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard as BaseDashboard;
use Webkul\Meetings\Filament\Widgets\MeetingApprovalsTable;
use Webkul\Meetings\Filament\Widgets\MeetingDashboardStats;
use Webkul\Meetings\Filament\Widgets\MeetingTasksTable;
use Webkul\Meetings\Filament\Widgets\RecentConfirmedMeetingsTable;
use Webkul\Meetings\Filament\Widgets\UpcomingMeetingsTable;

class MeetingDashboard extends BaseDashboard
{
    use HasPageShield;

    protected static string $routePath = 'meetings/dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 49;

    protected static function getPagePermission(): ?string
    {
        return 'view_any_meetings_meeting';
    }

    public static function getNavigationLabel(): string
    {
        return __('meetings::meetings.navigation.dashboard');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.meetings');
    }

    public function getHeaderWidgets(): array
    {
        return [
            MeetingDashboardStats::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            UpcomingMeetingsTable::class,
            MeetingTasksTable::class,
            MeetingApprovalsTable::class,
            RecentConfirmedMeetingsTable::class,
        ];
    }
}
