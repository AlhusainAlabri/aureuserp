<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Recruitment\Models\Candidate;

class CandidatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('view_any_recruitment_candidate');
    }

    public function view(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('view_recruitment_candidate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_recruitment_candidate');
    }

    public function update(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('update_recruitment_candidate');
    }

    public function delete(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('delete_recruitment_candidate');
    }

    public function deleteAny(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('delete_any_recruitment_candidate');
    }

    public function restore(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('restore_recruitment_candidate');
    }

    public function restoreAny(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('restore_any_recruitment_candidate');
    }

    public function forceDelete(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('force_delete_recruitment_candidate');
    }

    public function forceDeleteAny(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('force_delete_any_recruitment_candidate');
    }
}
