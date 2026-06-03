<?php

namespace App\Filament\Assets\Pages;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Assets\Enums\BorrowingStatus;

class MyBorrowedAssets extends BaseBorrowingRequestsPage
{
    protected static ?string $slug = 'assets/my-borrowed-assets';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 3;

    protected static function getPagePermission(): ?string
    {
        return 'page_my_borrowed_assets';
    }

    public static function getNavigationLabel(): string
    {
        return __('assets-extensions::navigation.my_borrowed_assets');
    }

    public static function getNavigationGroup(): string
    {
        return __('assets::assets.navigation.group');
    }

    public function getTitle(): string
    {
        return __('assets-extensions::navigation.my_borrowed_assets');
    }

    protected function scopedQuery(): Builder
    {
        return $this->employeeScopedQuery()
            ->whereIn('status', [BorrowingStatus::Active, BorrowingStatus::Overdue]);
    }
}
