<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Manufacturing\Models\Operation;

class OperationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('view_any_manufacturing_operation');
    }

    public function view(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('view_manufacturing_operation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_manufacturing_operation');
    }

    public function update(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('update_manufacturing_operation');
    }

    public function delete(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('delete_manufacturing_operation');
    }

    public function deleteAny(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('delete_any_manufacturing_operation');
    }

    public function restore(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('restore_manufacturing_operation');
    }

    public function restoreAny(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('restore_any_manufacturing_operation');
    }

    public function forceDelete(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('force_delete_manufacturing_operation');
    }

    public function forceDeleteAny(AuthUser $authUser, Operation $operation): bool
    {
        return $authUser->can('force_delete_any_manufacturing_operation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_manufacturing_operation');
    }
}
