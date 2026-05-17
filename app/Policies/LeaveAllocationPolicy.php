<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\TimeOff\Models\LeaveAllocation;

class LeaveAllocationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, LeaveAllocation $leaveAllocation): bool
    {
        return $authUser->can('view_any_time_off_my::allocation');
    }

    public function view(AuthUser $authUser, LeaveAllocation $leaveAllocation): bool
    {
        return $authUser->can('view_time_off_my::allocation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_time_off_my::allocation');
    }

    public function update(AuthUser $authUser, LeaveAllocation $leaveAllocation): bool
    {
        return $authUser->can('update_time_off_my::allocation');
    }

    public function delete(AuthUser $authUser, LeaveAllocation $leaveAllocation): bool
    {
        return $authUser->can('delete_time_off_my::allocation');
    }

    public function deleteAny(AuthUser $authUser, LeaveAllocation $leaveAllocation): bool
    {
        return $authUser->can('delete_any_time_off_my::allocation');
    }
}
