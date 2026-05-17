<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;

class UpcomingMeetingsTable extends TableWidget
{
    use HasMeetingVisibility;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return __('meetings::meetings.dashboard.sections.upcoming');
    }

    protected function getTableQuery(): Builder
    {
        return $this->visibleMeetingsQuery()
            ->withCount('attendees')
            ->whereBetween('meeting_date', [now(), now()->addDays(30)])
            ->orderBy('meeting_date')
            ->limit(8);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('meeting_number')->label(__('meetings::meetings.fields.meeting_number'))->description(fn ($record) => $record->location),
            TextColumn::make('title')->label(__('meetings::meetings.fields.title'))->weight('bold')->wrap(),
            TextColumn::make('meeting_date')->label(__('meetings::meetings.fields.meeting_date'))->dateTime('l, d F Y - h:i A'),
            TextColumn::make('status')->label(__('meetings::meetings.fields.status'))->formatStateUsing(fn ($state) => MeetingResource::statusOptions()[$state] ?? $state)->badge(),
            TextColumn::make('attendees_count')->label(__('meetings::meetings.fields.attendees_count')),
        ];
    }

    protected function getTableRecordActions(): array
    {
        return [
            ViewAction::make()->url(fn ($record) => MeetingResource::getUrl('view', ['record' => $record])),
        ];
    }
}
