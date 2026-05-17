<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Manufacturing\Models\BillOfMaterial;

class BillOfMaterialPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('view_any_manufacturing_bills::of::material');
    }

    public function view(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('view_manufacturing_bills::of::material');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_manufacturing_bills::of::material');
    }

    public function update(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('update_manufacturing_bills::of::material');
    }

    public function delete(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('delete_manufacturing_bills::of::material');
    }

    public function deleteAny(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('delete_any_manufacturing_bills::of::material');
    }

    public function restore(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('restore_manufacturing_bills::of::material');
    }

    public function restoreAny(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('restore_any_manufacturing_bills::of::material');
    }

    public function forceDelete(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('force_delete_manufacturing_bills::of::material');
    }

    public function forceDeleteAny(AuthUser $authUser, BillOfMaterial $billOfMaterial): bool
    {
        return $authUser->can('force_delete_any_manufacturing_bills::of::material');
    }
}
