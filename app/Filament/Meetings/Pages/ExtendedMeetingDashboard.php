<?php

namespace App\Filament\Meetings\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Webkul\Meetings\Filament\Pages\MeetingDashboard as BaseMeetingDashboard;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\MeetingApprovalsTable;
use Webkul\Meetings\Filament\Widgets\MeetingDashboardStats;
use Webkul\Meetings\Filament\Widgets\MeetingsStatusChartWidget;
use Webkul\Meetings\Filament\Widgets\MeetingsTrendChartWidget;
use Webkul\Meetings\Filament\Widgets\MeetingTasksTable;
use Webkul\Meetings\Filament\Widgets\RecentConfirmedMeetingsTable;
use Webkul\Meetings\Filament\Widgets\UpcomingMeetingsTable;

class ExtendedMeetingDashboard extends BaseMeetingDashboard
{
    use InteractsWithAdvancedDashboard;

    protected string $view = 'filament.pages.advanced-dashboard';

    public function getTitle(): string
    {
        return __('dashboard.hub.meetings');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.hub.meetings_description');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            $this->configureFilterSection(
                Section::make()
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
                            ->default('all')
                            ->native(false),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ),
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
}
