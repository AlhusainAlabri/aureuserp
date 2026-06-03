<?php

namespace Webkul\Assets\Policies;

use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Security\Models\User;

class AssetBorrowingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_assets_asset_borrowing')
            || $user->can('view_any_assets_asset');
    }

    public function view(User $user, AssetBorrowing $borrowing): bool
    {
        if ($user->can('view_assets_asset_borrowing') || $user->can('view_any_assets_asset')) {
            return true;
        }

        return $borrowing->employee?->user_id === $user->id;
    }

    public function approve(User $user, AssetBorrowing $borrowing): bool
    {
        return $borrowing->status === BorrowingStatus::Pending
            && $user->can('approve_borrowing_assets_asset');
    }

    public function reject(User $user, AssetBorrowing $borrowing): bool
    {
        return $borrowing->status === BorrowingStatus::Pending
            && $user->can('reject_borrowing_assets_asset');
    }
}
