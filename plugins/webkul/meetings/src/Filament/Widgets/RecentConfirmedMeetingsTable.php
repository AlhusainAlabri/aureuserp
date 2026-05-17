<?php

namespace Webkul\Meetings\Filament\Widgets;

use App\Filament\Actions\ExportMeetingPdfAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;

class RecentConfirmedMeetingsTable extends TableWidget
{
    use HasMeetingVisibility;

    protected int|string|array $columnSpan = 5;

    public function getHeading(): string
    {
        return __('meetings::meetings.dashboard.sections.recent_confirmed');
    }

    protected function getTableQuery(): Builder
    {
        return $this->visibleMeetingsQuery()
            ->confirmed()
            ->with('chairPerson')
            ->latest('meeting_date')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('meeting_number')->label(__('meetings::meetings.fields.meeting_number')),
            TextColumn::make('title')->label(__('meetings::meetings.fields.title'))->wrap(),
            TextColumn::make('meeting_date')->label(__('meetings::meetings.fields.meeting_date'))->date(),
            TextColumn::make('chairPerson.name')->label(__('meetings::meetings.fields.chair_person')),
        ];
    }

    protected function getTableRecordActions(): array
    {
        return [
            ViewAction::make()->url(fn ($record) => MeetingResource::getUrl('view', ['record' => $record])),
            ExportMeetingPdfAction::make(),
        ];
    }
}
