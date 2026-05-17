<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Support\Models\Company;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('view_any_support_company');
    }

    public function view(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('view_support_company');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_support_company');
    }

    public function update(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('update_support_company');
    }

    public function delete(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('delete_support_company');
    }

    public function deleteAny(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('delete_any_support_company');
    }

    public function restore(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('restore_support_company');
    }

    public function restoreAny(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('restore_any_support_company');
    }

    public function forceDelete(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('force_delete_support_company');
    }

    public function forceDeleteAny(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('force_delete_any_support_company');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_support_company');
    }
}
