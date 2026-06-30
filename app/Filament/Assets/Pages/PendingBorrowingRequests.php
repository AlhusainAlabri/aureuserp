<?php

namespace App\Filament\Assets\Pages;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Models\AssetBorrowing;

class PendingBorrowingRequests extends BaseBorrowingRequestsPage
{
    protected static ?string $slug = 'assets/pending-borrowing-requests';

    protected static ?int $navigationSort = 1;

    protected static function getPagePermission(): ?string
    {
        return 'page_pending_borrowing_requests';
    }

    public static function getNavigationLabel(): string
    {
        return __('assets-extensions::navigation.pending_requests');
    }

    public static function getNavigationGroup(): string
    {
        return __('assets::assets.navigation.group');
    }

    public function getTitle(): string
    {
        return __('assets-extensions::navigation.pending_requests');
    }

    protected function scopedQuery(): Builder
    {
        return AssetBorrowing::query()
            ->with(['asset', 'employee', 'requestedBy'])
            ->where('status', BorrowingStatus::Pending)
            ->orderByDesc('created_at');
    }
}
