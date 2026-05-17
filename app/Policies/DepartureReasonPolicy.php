<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Employee\Models\DepartureReason;

class DepartureReasonPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, DepartureReason $departureReason): bool
    {
        return $authUser->can('view_any_employee_departure::reason');
    }

    public function view(AuthUser $authUser, DepartureReason $departureReason): bool
    {
        return $authUser->can('view_employee_departure::reason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_employee_departure::reason');
    }

    public function update(AuthUser $authUser, DepartureReason $departureReason): bool
    {
        return $authUser->can('update_employee_departure::reason');
    }

    public function delete(AuthUser $authUser, DepartureReason $departureReason): bool
    {
        return $authUser->can('delete_employee_departure::reason');
    }

    public function deleteAny(AuthUser $authUser, DepartureReason $departureReason): bool
    {
        return $authUser->can('delete_any_employee_departure::reason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_employee_departure::reason');
    }
}
