<?php

namespace Webkul\Meetings\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\MeetingApprovalsTable;
use Webkul\Meetings\Filament\Widgets\MeetingDashboardStats;
use Webkul\Meetings\Filament\Widgets\MeetingsStatusChartWidget;
use Webkul\Meetings\Filament\Widgets\MeetingsTrendChartWidget;
use Webkul\Meetings\Filament\Widgets\MeetingTasksTable;
use Webkul\Meetings\Filament\Widgets\RecentConfirmedMeetingsTable;
use Webkul\Meetings\Filament\Widgets\UpcomingMeetingsTable;

class MeetingDashboard extends BaseDashboard
{
    use HasFiltersForm;
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

    public function getTitle(): string
    {
        return __('meetings::meetings.navigation.dashboard');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('meetings::meetings.dashboard.filters.title'))
                ->schema([
                    DatePicker::make('startDate')
                        ->label(__('meetings::meetings.dashboard.filters.start_date'))
                        ->default(now()->startOfYear()->format('Y-m-d'))
                        ->maxDate(fn (Get $get): ?string => $get('endDate') ?: now()->format('Y-m-d'))
                        ->native(false),
                    DatePicker::make('endDate')
                        ->label(__('meetings::meetings.dashboard.filters.end_date'))
                        ->default(now()->endOfYear()->format('Y-m-d'))
                        ->minDate(fn (Get $get): ?string => $get('startDate'))
                        ->maxDate(now()->format('Y-m-d'))
                        ->native(false),
                    Select::make('status')
                        ->label(__('meetings::meetings.dashboard.filters.status'))
                        ->options([
                            'all' => __('meetings::meetings.dashboard.filters.all_statuses'),
                            ...MeetingResource::statusOptions(),
                        ])
                        ->default('all'),
                ])
                ->columns(3),
        ]);
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
            MeetingsTrendChartWidget::class,
            MeetingsStatusChartWidget::class,
            UpcomingMeetingsTable::class,
            MeetingApprovalsTable::class,
            MeetingTasksTable::class,
            RecentConfirmedMeetingsTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'lg'      => 12,
        ];
    }
}
