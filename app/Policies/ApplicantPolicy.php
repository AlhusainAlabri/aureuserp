<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\Applicant;

class ApplicantPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('view_any_recruitment_applicant');
    }

    public function view(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('view_recruitment_applicant');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_applicant');
    }

    public function update(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('update_recruitment_applicant');
    }

    public function delete(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('delete_recruitment_applicant');
    }

    public function deleteAny(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('delete_any_recruitment_applicant');
    }

    public function restore(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('restore_recruitment_applicant');
    }

    public function restoreAny(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('restore_any_recruitment_applicant');
    }

    public function forceDelete(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('force_delete_recruitment_applicant');
    }

    public function forceDeleteAny(AuthUser $authUser, Applicant $applicant): bool
    {
        return $authUser->can('force_delete_any_recruitment_applicant');
    }
}
