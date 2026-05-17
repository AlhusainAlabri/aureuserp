<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\RefuseReason;

class RefuseReasonPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, RefuseReason $refuseReason): bool
    {
        return $authUser->can('view_any_recruitment_refuse::reason');
    }

    public function view(AuthUser $authUser, RefuseReason $refuseReason): bool
    {
        return $authUser->can('view_recruitment_refuse::reason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_refuse::reason');
    }

    public function update(AuthUser $authUser, RefuseReason $refuseReason): bool
    {
        return $authUser->can('update_recruitment_refuse::reason');
    }

    public function delete(AuthUser $authUser, RefuseReason $refuseReason): bool
    {
        return $authUser->can('delete_recruitment_refuse::reason');
    }

    public function deleteAny(AuthUser $authUser, RefuseReason $refuseReason): bool
    {
        return $authUser->can('delete_any_recruitment_refuse::reason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_recruitment_refuse::reason');
    }
}
