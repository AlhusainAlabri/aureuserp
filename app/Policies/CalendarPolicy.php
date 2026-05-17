<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Support\Models\Calendar;

class CalendarPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('view_any_support_calendar');
    }

    public function view(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('view_support_calendar');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_support_calendar');
    }

    public function update(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('update_support_calendar');
    }

    public function delete(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('delete_support_calendar');
    }

    public function deleteAny(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('delete_any_support_calendar');
    }

    public function restore(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('restore_support_calendar');
    }

    public function restoreAny(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('restore_any_support_calendar');
    }

    public function forceDelete(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('force_delete_support_calendar');
    }

    public function forceDeleteAny(AuthUser $authUser, Calendar $calendar): bool
    {
        return $authUser->can('force_delete_any_support_calendar');
    }
}
