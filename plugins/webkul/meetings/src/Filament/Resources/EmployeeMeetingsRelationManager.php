<?php

namespace Webkul\Meetings\Filament\Resources;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeMeetingsRelationManager extends RelationManager
{
    protected static string $relationship = 'meetingAttendances';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.employee_meetings');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meeting.meeting_number')->label(__('meetings::meetings.fields.meeting_number')),
                TextColumn::make('meeting.title')->label(__('meetings::meetings.fields.title'))->wrap(),
                TextColumn::make('role')->label(__('meetings::meetings.fields.role'))->badge(),
                TextColumn::make('meeting.meeting_date')->label(__('meetings::meetings.fields.meeting_date'))->dateTime(),
                TextColumn::make('meeting.status')->label(__('meetings::meetings.fields.status'))->badge(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => MeetingResource::getUrl('view', ['record' => $record->meeting])),
            ]);
    }
}
