<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\TimeOff\Models\LeaveAccrualPlan;

class LeaveAccrualPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, LeaveAccrualPlan $leaveAccrualPlan): bool
    {
        return $authUser->can('view_any_time_off_accrual::plan');
    }

    public function view(AuthUser $authUser, LeaveAccrualPlan $leaveAccrualPlan): bool
    {
        return $authUser->can('view_time_off_accrual::plan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_time_off_accrual::plan');
    }

    public function update(AuthUser $authUser, LeaveAccrualPlan $leaveAccrualPlan): bool
    {
        return $authUser->can('update_time_off_accrual::plan');
    }

    public function delete(AuthUser $authUser, LeaveAccrualPlan $leaveAccrualPlan): bool
    {
        return $authUser->can('delete_time_off_accrual::plan');
    }

    public function deleteAny(AuthUser $authUser, LeaveAccrualPlan $leaveAccrualPlan): bool
    {
        return $authUser->can('delete_any_time_off_accrual::plan');
    }
}
