<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Timesheet\Models\Timesheet;

class TimesheetPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('view_any_timesheet_timesheet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_timesheet_timesheet');
    }

    public function update(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('update_timesheet_timesheet');
    }

    public function delete(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('delete_timesheet_timesheet');
    }

    public function deleteAny(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('delete_any_timesheet_timesheet');
    }
}
