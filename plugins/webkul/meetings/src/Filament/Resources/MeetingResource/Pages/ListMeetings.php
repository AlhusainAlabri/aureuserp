<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Models\Meeting;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('meetings::meetings.actions.create')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('meetings::meetings.all'))
                ->badge(Meeting::query()->count()),
            'draft' => Tab::make(__('meetings::meetings.statuses.draft'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->draft())
                ->badge(Meeting::query()->draft()->count()),
            'pending_approval' => Tab::make(__('meetings::meetings.statuses.pending_approval'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->pendingApproval())
                ->badge(Meeting::query()->pendingApproval()->count()),
            'approved' => Tab::make(__('meetings::meetings.statuses.approved'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->approved())
                ->badge(Meeting::query()->approved()->count()),
            'confirmed' => Tab::make(__('meetings::meetings.statuses.confirmed'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->confirmed())
                ->badge(Meeting::query()->confirmed()->count()),
            'archived' => Tab::make(__('meetings::meetings.statuses.archived'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->archived())
                ->badge(Meeting::query()->archived()->count()),
        ];
    }
}
