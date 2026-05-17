<?php

namespace Webkul\Meetings\Filament\Resources;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectMeetingsRelationManager extends RelationManager
{
    protected static string $relationship = 'meetings';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.project_meetings');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meeting_number')->label(__('meetings::meetings.fields.meeting_number')),
                TextColumn::make('title')->label(__('meetings::meetings.fields.title'))->wrap(),
                TextColumn::make('status')->label(__('meetings::meetings.fields.status'))->badge(),
                TextColumn::make('meeting_date')->label(__('meetings::meetings.fields.meeting_date'))->dateTime(),
                TextColumn::make('chairPerson.name')->label(__('meetings::meetings.fields.chair_person')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => MeetingResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
