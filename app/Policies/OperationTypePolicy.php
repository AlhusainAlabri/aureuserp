<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\OperationType;

class OperationTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('view_any_inventory_operation::type');
    }

    public function view(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('view_inventory_operation::type');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_operation::type');
    }

    public function update(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('update_inventory_operation::type');
    }

    public function delete(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('delete_inventory_operation::type');
    }

    public function deleteAny(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('delete_any_inventory_operation::type');
    }

    public function restore(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('restore_inventory_operation::type');
    }

    public function restoreAny(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('restore_any_inventory_operation::type');
    }

    public function forceDelete(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('force_delete_inventory_operation::type');
    }

    public function forceDeleteAny(AuthUser $authUser, OperationType $operationType): bool
    {
        return $authUser->can('force_delete_any_inventory_operation::type');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_inventory_operation::type');
    }
}
