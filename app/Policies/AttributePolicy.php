<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\Attribute;

class AttributePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('view_any_sale_product::attribute');
    }

    public function view(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('view_sale_product::attribute');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_product::attribute');
    }

    public function update(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('update_sale_product::attribute');
    }

    public function delete(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('delete_sale_product::attribute');
    }

    public function deleteAny(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('delete_any_sale_product::attribute');
    }

    public function restore(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('restore_sale_product::attribute');
    }

    public function restoreAny(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('restore_any_sale_product::attribute');
    }

    public function forceDelete(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('force_delete_sale_product::attribute');
    }

    public function forceDeleteAny(AuthUser $authUser, Attribute $attribute): bool
    {
        return $authUser->can('force_delete_any_sale_product::attribute');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_sale_product::attribute');
    }
}
