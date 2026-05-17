<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Support\Models\Country;

class CountryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Country $country): bool
    {
        return $authUser->can('view_any_support_country');
    }

    public function view(AuthUser $authUser, Country $country): bool
    {
        return $authUser->can('view_support_country');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_support_country');
    }

    public function update(AuthUser $authUser, Country $country): bool
    {
        return $authUser->can('update_support_country');
    }

    public function delete(AuthUser $authUser, Country $country): bool
    {
        return $authUser->can('delete_support_country');
    }

    public function deleteAny(AuthUser $authUser, Country $country): bool
    {
        return $authUser->can('delete_any_support_country');
    }
}
