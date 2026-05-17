<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Inventory\Models\Receipt;

class ReceiptPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Receipt $receipt): bool
    {
        return $authUser->can('view_any_inventory_receipt');
    }

    public function view(AuthUser $authUser, Receipt $receipt): bool
    {
        return $authUser->can('view_inventory_receipt');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_inventory_receipt');
    }

    public function update(AuthUser $authUser, Receipt $receipt): bool
    {
        return $authUser->can('update_inventory_receipt');
    }

    public function delete(AuthUser $authUser, Receipt $receipt): bool
    {
        return $authUser->can('delete_inventory_receipt');
    }

    public function deleteAny(AuthUser $authUser, Receipt $receipt): bool
    {
        return $authUser->can('delete_any_inventory_receipt');
    }
}
