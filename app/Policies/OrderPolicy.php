<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Manufacturing\Models\Order;

class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('view_any_manufacturing_manufacturing::order');
    }

    public function view(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('view_manufacturing_manufacturing::order');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_manufacturing_manufacturing::order');
    }

    public function update(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('update_manufacturing_manufacturing::order');
    }

    public function delete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('delete_manufacturing_manufacturing::order');
    }

    public function deleteAny(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('delete_any_manufacturing_manufacturing::order');
    }
}
