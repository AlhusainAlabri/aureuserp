<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Meetings\Models\Meeting;

class MeetingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('view_any_meetings_meeting');
    }

    public function view(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('view_meetings_meeting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_meetings_meeting');
    }

    public function update(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('update_meetings_meeting');
    }

    public function delete(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('delete_meetings_meeting');
    }

    public function deleteAny(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('delete_any_meetings_meeting');
    }

    public function archive(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('archive_meetings_meeting');
    }

    public function confirm(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('confirm_meetings_meeting');
    }

    public function exportPdf(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('export_pdf_meetings_meeting');
    }

    public function manageTasks(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('manage_tasks_meetings_meeting');
    }

    public function manageAttendees(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('manage_attendees_meetings_meeting');
    }

    public function manageAttachments(AuthUser $authUser, Meeting $meeting): bool
    {
        return $authUser->can('manage_attachments_meetings_meeting');
    }
}
