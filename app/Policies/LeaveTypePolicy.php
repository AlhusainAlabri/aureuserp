<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\TimeOff\Models\LeaveType;

class LeaveTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('view_any_time_off_leave::type');
    }

    public function view(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('view_time_off_leave::type');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_time_off_leave::type');
    }

    public function update(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('update_time_off_leave::type');
    }

    public function delete(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('delete_time_off_leave::type');
    }

    public function deleteAny(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('delete_any_time_off_leave::type');
    }

    public function restore(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('restore_time_off_leave::type');
    }

    public function restoreAny(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('restore_any_time_off_leave::type');
    }

    public function forceDelete(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('force_delete_time_off_leave::type');
    }

    public function forceDeleteAny(AuthUser $authUser, LeaveType $leaveType): bool
    {
        return $authUser->can('force_delete_any_time_off_leave::type');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_time_off_leave::type');
    }
}
