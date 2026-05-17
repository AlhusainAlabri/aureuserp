<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\TimeOff\Models\ActivityType;

class ActivityTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('view_any_time_off_activity::type');
    }

    public function view(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('view_time_off_activity::type');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_time_off_activity::type');
    }

    public function update(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('update_time_off_activity::type');
    }

    public function delete(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('delete_time_off_activity::type');
    }

    public function deleteAny(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('delete_any_time_off_activity::type');
    }

    public function restore(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('restore_time_off_activity::type');
    }

    public function restoreAny(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('restore_any_time_off_activity::type');
    }

    public function forceDelete(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('force_delete_time_off_activity::type');
    }

    public function forceDeleteAny(AuthUser $authUser, ActivityType $activityType): bool
    {
        return $authUser->can('force_delete_any_time_off_activity::type');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_time_off_activity::type');
    }
}
