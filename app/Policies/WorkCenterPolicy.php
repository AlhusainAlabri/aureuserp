<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Manufacturing\Models\WorkCenter;

class WorkCenterPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('view_any_manufacturing_work::center');
    }

    public function view(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('view_manufacturing_work::center');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_manufacturing_work::center');
    }

    public function update(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('update_manufacturing_work::center');
    }

    public function delete(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('delete_manufacturing_work::center');
    }

    public function deleteAny(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('delete_any_manufacturing_work::center');
    }

    public function restore(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('restore_manufacturing_work::center');
    }

    public function restoreAny(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('restore_any_manufacturing_work::center');
    }

    public function forceDelete(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('force_delete_manufacturing_work::center');
    }

    public function forceDeleteAny(AuthUser $authUser, WorkCenter $workCenter): bool
    {
        return $authUser->can('force_delete_any_manufacturing_work::center');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_manufacturing_work::center');
    }
}
