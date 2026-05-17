<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\TimeOff\Models\LeaveMandatoryDay;

class LeaveMandatoryDayPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, LeaveMandatoryDay $leaveMandatoryDay): bool
    {
        return $authUser->can('view_any_time_off_mandatory::day');
    }

    public function view(AuthUser $authUser, LeaveMandatoryDay $leaveMandatoryDay): bool
    {
        return $authUser->can('view_time_off_mandatory::day');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_time_off_mandatory::day');
    }

    public function update(AuthUser $authUser, LeaveMandatoryDay $leaveMandatoryDay): bool
    {
        return $authUser->can('update_time_off_mandatory::day');
    }

    public function delete(AuthUser $authUser, LeaveMandatoryDay $leaveMandatoryDay): bool
    {
        return $authUser->can('delete_time_off_mandatory::day');
    }

    public function deleteAny(AuthUser $authUser, LeaveMandatoryDay $leaveMandatoryDay): bool
    {
        return $authUser->can('delete_any_time_off_mandatory::day');
    }
}
