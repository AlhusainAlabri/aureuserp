<?php

namespace Webkul\Meetings\Traits;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Meetings\Filament\Resources\MeetingResource;

trait EmployeeMeetingsRelation
{
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
