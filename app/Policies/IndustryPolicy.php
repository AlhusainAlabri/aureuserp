<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Partner\Models\Industry;

class IndustryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('view_any_partner_industry');
    }

    public function view(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('view_partner_industry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_partner_industry');
    }

    public function update(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('update_partner_industry');
    }

    public function delete(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('delete_partner_industry');
    }

    public function deleteAny(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('delete_any_partner_industry');
    }

    public function restore(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('restore_partner_industry');
    }

    public function restoreAny(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('restore_any_partner_industry');
    }

    public function forceDelete(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('force_delete_partner_industry');
    }

    public function forceDeleteAny(AuthUser $authUser, Industry $industry): bool
    {
        return $authUser->can('force_delete_any_partner_industry');
    }
}
