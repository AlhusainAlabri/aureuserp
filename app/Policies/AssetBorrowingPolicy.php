<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Assets\Models\AssetBorrowing;

class AssetBorrowingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('view_any_filament_asset::borrowing');
    }

    public function view(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('view_filament_asset::borrowing');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_filament_asset::borrowing');
    }

    public function update(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('update_filament_asset::borrowing');
    }

    public function delete(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('delete_filament_asset::borrowing');
    }

    public function restore(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('restore_filament_asset::borrowing');
    }

    public function deleteAny(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('delete_any_filament_asset::borrowing');
    }

    public function forceDelete(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('force_delete_filament_asset::borrowing');
    }

    public function forceDeleteAny(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('force_delete_any_filament_asset::borrowing');
    }

    public function restoreAny(AuthUser $authUser, AssetBorrowing $assetBorrowing): bool
    {
        return $authUser->can('restore_any_filament_asset::borrowing');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_filament_asset::borrowing');
    }
}
