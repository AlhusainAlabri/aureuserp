<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\TimeOff\Models\Leave;

class LeavePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Leave $leave): bool
    {
        return $authUser->can('view_any_time_off_by::employee');
    }

    public function view(AuthUser $authUser, Leave $leave): bool
    {
        return $authUser->can('view_time_off_by::employee');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_time_off_by::employee');
    }

    public function update(AuthUser $authUser, Leave $leave): bool
    {
        return $authUser->can('update_time_off_by::employee');
    }

    public function delete(AuthUser $authUser, Leave $leave): bool
    {
        return $authUser->can('delete_time_off_by::employee');
    }

    public function deleteAny(AuthUser $authUser, Leave $leave): bool
    {
        return $authUser->can('delete_any_time_off_by::employee');
    }
}
