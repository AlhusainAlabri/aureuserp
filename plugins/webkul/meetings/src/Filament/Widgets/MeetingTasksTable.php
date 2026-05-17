<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;

class MeetingTasksTable extends TableWidget
{
    use HasMeetingVisibility;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return __('meetings::meetings.dashboard.sections.my_tasks');
    }

    protected function getTableQuery(): Builder
    {
        return $this->visibleTasksQuery()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('meeting')
            ->orderBy('due_date')
            ->limit(6);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('priority')->label(__('meetings::meetings.fields.priority'))->formatStateUsing(fn ($state) => MeetingResource::priorityOptions()[$state] ?? $state)->badge(),
            TextColumn::make('title')->label(__('meetings::meetings.fields.task_title'))->wrap(),
            TextColumn::make('meeting.meeting_number')->label(__('meetings::meetings.fields.meeting_number'))->url(fn ($record) => MeetingResource::getUrl('view', ['record' => $record->meeting])),
            TextColumn::make('due_date')->label(__('meetings::meetings.fields.due_date'))->date()->color(fn ($record) => $record->due_date?->isPast() ? 'danger' : null),
            SelectColumn::make('status')->label(__('meetings::meetings.fields.status'))->options(MeetingResource::taskStatusOptions()),
        ];
    }
}
