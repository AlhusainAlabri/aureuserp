<?php

namespace Webkul\Meetings\Policies;

use Webkul\Meetings\Models\Meeting;
use Webkul\Security\Models\User;

class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_meetings_meeting');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->can('view_meetings_meeting');
    }

    public function create(User $user): bool
    {
        return $user->can('create_meetings_meeting');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $meeting->isDraft() && $user->can('update_meetings_meeting');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->can('delete_meetings_meeting');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_meetings_meeting');
    }

    public function archive(User $user, Meeting $meeting): bool
    {
        return $meeting->isConfirmed() && $user->can('archive_meetings_meeting');
    }

    public function confirm(User $user, Meeting $meeting): bool
    {
        return $meeting->status === 'approved' && $user->can('confirm_meetings_meeting');
    }

    public function exportPdf(User $user, Meeting $meeting): bool
    {
        return $user->can('export_pdf_meetings_meeting');
    }

    public function manageTasks(User $user, Meeting $meeting): bool
    {
        return $user->can('manage_tasks_meetings_meeting');
    }

    public function manageAttendees(User $user, Meeting $meeting): bool
    {
        return $user->can('manage_attendees_meetings_meeting');
    }
}
