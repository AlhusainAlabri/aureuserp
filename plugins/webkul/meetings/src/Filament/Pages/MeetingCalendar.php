<?php

namespace Webkul\Meetings\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard as BaseDashboard;
use Webkul\Meetings\Filament\Widgets\MeetingCalendarWidget;

class MeetingCalendar extends BaseDashboard
{
    use HasPageShield;

    protected static string $routePath = 'meetings/calendar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 51;

    protected static function getPagePermission(): ?string
    {
        return 'view_any_meetings_meeting';
    }

    public static function getNavigationLabel(): string
    {
        return __('meetings::meetings.navigation.calendar');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.meetings');
    }

    public function getWidgets(): array
    {
        return [
            MeetingCalendarWidget::class,
        ];
    }
}
