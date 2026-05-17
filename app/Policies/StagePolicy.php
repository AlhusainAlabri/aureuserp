<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\Stage;

class StagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Stage $stage): bool
    {
        return $authUser->can('view_any_recruitment_stage');
    }

    public function view(AuthUser $authUser, Stage $stage): bool
    {
        return $authUser->can('view_recruitment_stage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_stage');
    }

    public function update(AuthUser $authUser, Stage $stage): bool
    {
        return $authUser->can('update_recruitment_stage');
    }

    public function delete(AuthUser $authUser, Stage $stage): bool
    {
        return $authUser->can('delete_recruitment_stage');
    }

    public function deleteAny(AuthUser $authUser, Stage $stage): bool
    {
        return $authUser->can('delete_any_recruitment_stage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_recruitment_stage');
    }
}
