<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

class ApprovalFlowPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('view_any_filament_approval_approval::flow');
    }

    public function view(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('view_filament_approval_approval::flow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_filament_approval_approval::flow');
    }

    public function update(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('update_filament_approval_approval::flow');
    }

    public function delete(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('delete_filament_approval_approval::flow');
    }

    public function restore(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('restore_filament_approval_approval::flow');
    }

    public function deleteAny(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('delete_any_filament_approval_approval::flow');
    }

    public function forceDelete(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('force_delete_filament_approval_approval::flow');
    }

    public function forceDeleteAny(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('force_delete_any_filament_approval_approval::flow');
    }

    public function restoreAny(AuthUser $authUser, ApprovalFlow $approvalFlow): bool
    {
        return $authUser->can('restore_any_filament_approval_approval::flow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_filament_approval_approval::flow');
    }
}
