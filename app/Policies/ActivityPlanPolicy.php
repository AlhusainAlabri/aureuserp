<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\ActivityPlan;

class ActivityPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('view_any_sale_activity::plan');
    }

    public function view(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('view_sale_activity::plan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_activity::plan');
    }

    public function update(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('update_sale_activity::plan');
    }

    public function delete(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('delete_sale_activity::plan');
    }

    public function deleteAny(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('delete_any_sale_activity::plan');
    }

    public function restore(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('restore_sale_activity::plan');
    }

    public function restoreAny(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('restore_any_sale_activity::plan');
    }

    public function forceDelete(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('force_delete_sale_activity::plan');
    }

    public function forceDeleteAny(AuthUser $authUser, ActivityPlan $activityPlan): bool
    {
        return $authUser->can('force_delete_any_sale_activity::plan');
    }
}
