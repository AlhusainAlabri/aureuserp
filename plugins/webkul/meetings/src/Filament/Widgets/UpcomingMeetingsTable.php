<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Filament\Widgets\Concerns\InteractsWithMeetingDashboardFilters;
use Webkul\Meetings\Models\Meeting;

class UpcomingMeetingsTable extends TableWidget
{
    use HasMeetingVisibility;
    use InteractsWithMeetingDashboardFilters;
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('meetings::meetings.dashboard.sections.upcoming');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->filteredMeetingsQuery()
                    ->withCount('attendees')
                    ->whereBetween('meeting_date', [now(), now()->addDays(30)])
                    ->orderBy('meeting_date')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('meeting_number')
                    ->label(__('meetings::meetings.fields.meeting_number'))
                    ->description(fn (Meeting $record): ?string => $record->location),
                TextColumn::make('title')
                    ->label(__('meetings::meetings.fields.title'))
                    ->weight('bold')
                    ->wrap(),
                TextColumn::make('meeting_date')
                    ->label(__('meetings::meetings.fields.meeting_date'))
                    ->formatStateUsing(fn ($state, Meeting $record): string => $record->meeting_date?->translatedFormat('l، d F Y - h:i A') ?? '-'),
                TextColumn::make('status')
                    ->label(__('meetings::meetings.fields.status'))
                    ->formatStateUsing(fn (?string $state): string => MeetingResource::statusOptions()[$state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('attendees_count')
                    ->label(__('meetings::meetings.fields.attendees_count')),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('meetings::meetings.dashboard.actions.view_all'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(MeetingResource::getUrl('index'))
                    ->color('gray'),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Meeting $record): string => MeetingResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(__('meetings::meetings.empty.no_meetings'))
            ->emptyStateDescription(__('meetings::meetings.empty.no_meetings_description'))
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('meetings::meetings.actions.create'))
                    ->url(MeetingResource::getUrl('create')),
            ])
            ->paginated(false);
    }
}
