<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Manufacturing\Models\WorkOrder;

class WorkOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, WorkOrder $workOrder): bool
    {
        return $authUser->can('view_any_manufacturing_work::order');
    }

    public function view(AuthUser $authUser, WorkOrder $workOrder): bool
    {
        return $authUser->can('view_manufacturing_work::order');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_manufacturing_work::order');
    }

    public function update(AuthUser $authUser, WorkOrder $workOrder): bool
    {
        return $authUser->can('update_manufacturing_work::order');
    }

    public function delete(AuthUser $authUser, WorkOrder $workOrder): bool
    {
        return $authUser->can('delete_manufacturing_work::order');
    }

    public function deleteAny(AuthUser $authUser, WorkOrder $workOrder): bool
    {
        return $authUser->can('delete_any_manufacturing_work::order');
    }
}
