<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Assets\Models\Asset;

class AssetPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Asset $asset): bool
    {
        return $authUser->can('view_any_assets_asset');
    }

    public function view(AuthUser $authUser, Asset $asset): bool
    {
        return $authUser->can('view_assets_asset');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_assets_asset');
    }

    public function update(AuthUser $authUser, Asset $asset): bool
    {
        return $authUser->can('update_assets_asset');
    }

    public function delete(AuthUser $authUser, Asset $asset): bool
    {
        return $authUser->can('delete_assets_asset');
    }

    public function deleteAny(AuthUser $authUser, Asset $asset): bool
    {
        return $authUser->can('delete_any_assets_asset');
    }

    public function borrow(AuthUser $authUser, Asset $asset): bool
    {
        return $authUser->can('borrow_assets_asset');
    }

    public function returnAsset(AuthUser $authUser, Asset $asset): bool
    {
        return $authUser->can('return_asset_assets_asset');
    }
}
