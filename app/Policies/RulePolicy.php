<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Rule;

class RulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('view_any_inventory_rule');
    }

    public function view(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('view_inventory_rule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_rule');
    }

    public function update(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('update_inventory_rule');
    }

    public function delete(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('delete_inventory_rule');
    }

    public function deleteAny(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('delete_any_inventory_rule');
    }

    public function restore(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('restore_inventory_rule');
    }

    public function restoreAny(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('restore_any_inventory_rule');
    }

    public function forceDelete(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('force_delete_inventory_rule');
    }

    public function forceDeleteAny(AuthUser $authUser, Rule $rule): bool
    {
        return $authUser->can('force_delete_any_inventory_rule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_inventory_rule');
    }
}
