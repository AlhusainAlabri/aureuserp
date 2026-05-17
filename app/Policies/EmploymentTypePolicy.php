<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\EmploymentType;

class EmploymentTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, EmploymentType $employmentType): bool
    {
        return $authUser->can('view_any_recruitment_employment::type');
    }

    public function view(AuthUser $authUser, EmploymentType $employmentType): bool
    {
        return $authUser->can('view_recruitment_employment::type');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_employment::type');
    }

    public function update(AuthUser $authUser, EmploymentType $employmentType): bool
    {
        return $authUser->can('update_recruitment_employment::type');
    }

    public function delete(AuthUser $authUser, EmploymentType $employmentType): bool
    {
        return $authUser->can('delete_recruitment_employment::type');
    }

    public function deleteAny(AuthUser $authUser, EmploymentType $employmentType): bool
    {
        return $authUser->can('delete_any_recruitment_employment::type');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_recruitment_employment::type');
    }
}
