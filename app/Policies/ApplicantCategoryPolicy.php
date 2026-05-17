<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\ApplicantCategory;

class ApplicantCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, ApplicantCategory $applicantCategory): bool
    {
        return $authUser->can('view_any_recruitment_applicant::category');
    }

    public function view(AuthUser $authUser, ApplicantCategory $applicantCategory): bool
    {
        return $authUser->can('view_recruitment_applicant::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_applicant::category');
    }

    public function update(AuthUser $authUser, ApplicantCategory $applicantCategory): bool
    {
        return $authUser->can('update_recruitment_applicant::category');
    }

    public function delete(AuthUser $authUser, ApplicantCategory $applicantCategory): bool
    {
        return $authUser->can('delete_recruitment_applicant::category');
    }

    public function deleteAny(AuthUser $authUser, ApplicantCategory $applicantCategory): bool
    {
        return $authUser->can('delete_any_recruitment_applicant::category');
    }
}
