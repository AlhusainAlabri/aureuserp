<?php

namespace Webkul\Assets\Policies;

use Webkul\Assets\Models\Asset;
use Webkul\Security\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_assets_asset');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->can('view_assets_asset');
    }

    public function create(User $user): bool
    {
        return $user->can('create_assets_asset');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('update_assets_asset');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->can('delete_assets_asset');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_assets_asset');
    }

    public function borrow(User $user, Asset $asset): bool
    {
        return $asset->canBeBorrowed() && $user->can('borrow_assets_asset');
    }

    public function requestBorrow(User $user, Asset $asset): bool
    {
        return $asset->canBeBorrowed()
            && $user->can('request_borrow_assets_asset')
            && $user->employee !== null;
    }

    public function returnAsset(User $user, Asset $asset): bool
    {
        return $asset->isBorrowed()
            && $asset->activeBorrowing !== null
            && $user->can('return_asset_assets_asset');
    }
}
