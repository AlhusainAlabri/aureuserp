<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\JobPosition;

class JobPositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('view_any_recruitment_job::position');
    }

    public function view(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('view_recruitment_job::position');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_job::position');
    }

    public function update(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('update_recruitment_job::position');
    }

    public function delete(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('delete_recruitment_job::position');
    }

    public function deleteAny(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('delete_any_recruitment_job::position');
    }

    public function restore(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('restore_recruitment_job::position');
    }

    public function restoreAny(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('restore_any_recruitment_job::position');
    }

    public function forceDelete(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('force_delete_recruitment_job::position');
    }

    public function forceDeleteAny(AuthUser $authUser, JobPosition $jobPosition): bool
    {
        return $authUser->can('force_delete_any_recruitment_job::position');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_recruitment_job::position');
    }
}
