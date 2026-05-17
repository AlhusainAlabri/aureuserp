<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\TimeOff\Models\CalendarLeave;

class CalendarLeavePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, CalendarLeave $calendarLeave): bool
    {
        return $authUser->can('view_any_time_off_public::holiday');
    }

    public function view(AuthUser $authUser, CalendarLeave $calendarLeave): bool
    {
        return $authUser->can('view_time_off_public::holiday');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_time_off_public::holiday');
    }

    public function update(AuthUser $authUser, CalendarLeave $calendarLeave): bool
    {
        return $authUser->can('update_time_off_public::holiday');
    }

    public function delete(AuthUser $authUser, CalendarLeave $calendarLeave): bool
    {
        return $authUser->can('delete_time_off_public::holiday');
    }

    public function deleteAny(AuthUser $authUser, CalendarLeave $calendarLeave): bool
    {
        return $authUser->can('delete_any_time_off_public::holiday');
    }
}
