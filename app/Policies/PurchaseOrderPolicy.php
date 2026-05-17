<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Purchase\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('view_any_purchase_purchase::order');
    }

    public function view(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('view_purchase_purchase::order');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_purchase_purchase::order');
    }

    public function update(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('update_purchase_purchase::order');
    }

    public function delete(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('delete_purchase_purchase::order');
    }

    public function deleteAny(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('delete_any_purchase_purchase::order');
    }
}
