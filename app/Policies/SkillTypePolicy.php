<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\SkillType;

class SkillTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('view_any_recruitment_skill::type');
    }

    public function view(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('view_recruitment_skill::type');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_skill::type');
    }

    public function update(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('update_recruitment_skill::type');
    }

    public function delete(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('delete_recruitment_skill::type');
    }

    public function deleteAny(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('delete_any_recruitment_skill::type');
    }

    public function restore(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('restore_recruitment_skill::type');
    }

    public function restoreAny(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('restore_any_recruitment_skill::type');
    }

    public function forceDelete(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('force_delete_recruitment_skill::type');
    }

    public function forceDeleteAny(AuthUser $authUser, SkillType $skillType): bool
    {
        return $authUser->can('force_delete_any_recruitment_skill::type');
    }
}
