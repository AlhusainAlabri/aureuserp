<?php

namespace App\Filament\Assets\Pages;

use App\Filament\Assets\Actions\RequestAssetAction;
use Illuminate\Database\Eloquent\Builder;

class MyBorrowingRequests extends BaseBorrowingRequestsPage
{
    protected static ?string $slug = 'assets/my-borrowing-requests';

    protected static function getPagePermission(): ?string
    {
        return 'page_my_borrowing_requests';
    }

    public static function getNavigationLabel(): string
    {
        return __('assets-extensions::navigation.my_requests');
    }

    public static function getNavigationGroup(): string
    {
        return __('assets::assets.navigation.group');
    }

    public function getTitle(): string
    {
        return __('assets-extensions::navigation.my_requests');
    }

    protected function scopedQuery(): Builder
    {
        return $this->employeeScopedQuery();
    }

    protected function getHeaderActions(): array
    {
        return [
            RequestAssetAction::make(),
        ];
    }
}
