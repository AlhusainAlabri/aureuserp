<?php

namespace Webkul\Meetings\Filament\Widgets\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Security\Models\User;

trait HasMeetingVisibility
{
    protected function canSeeAllMeetings(?User $user): bool
    {
        return (bool) ($user?->can('view_any_meetings_meeting') || $user?->hasAnyRole(['admin', 'manager', 'admin_manager']));
    }

    protected function visibleMeetingsQuery(): Builder
    {
        $user = auth()->user();

        return Meeting::query()
            ->when(! $this->canSeeAllMeetings($user), fn (Builder $query): Builder => $query
                ->whereHas('attendees', fn (Builder $query): Builder => $query->where('user_id', $user?->id)));
    }

    protected function visibleTasksQuery(): Builder
    {
        $user = auth()->user();

        return MeetingTask::query()
            ->when(! $this->canSeeAllMeetings($user), fn (Builder $query): Builder => $query->where('assigned_to', $user?->id));
    }
}
