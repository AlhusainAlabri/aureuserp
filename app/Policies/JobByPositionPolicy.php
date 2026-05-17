<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\JobByPosition;

class JobByPositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('view_any_recruitment_job::by::position');
    }

    public function view(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('view_recruitment_job::by::position');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_job::by::position');
    }

    public function update(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('update_recruitment_job::by::position');
    }

    public function delete(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('delete_recruitment_job::by::position');
    }

    public function deleteAny(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('delete_any_recruitment_job::by::position');
    }

    public function restore(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('restore_recruitment_job::by::position');
    }

    public function restoreAny(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('restore_any_recruitment_job::by::position');
    }

    public function forceDelete(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('force_delete_recruitment_job::by::position');
    }

    public function forceDeleteAny(AuthUser $authUser, JobByPosition $jobByPosition): bool
    {
        return $authUser->can('force_delete_any_recruitment_job::by::position');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_recruitment_job::by::position');
    }
}
