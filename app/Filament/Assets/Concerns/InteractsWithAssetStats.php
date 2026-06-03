<?php

namespace App\Filament\Assets\Concerns;

use App\Filament\Assets\Pages\PendingBorrowingRequests;
use App\Support\FilamentUrl;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;

trait InteractsWithAssetStats
{
    protected function countAvailableAssets(): int
    {
        if (! Schema::hasTable('assets')) {
            return 0;
        }

        return Asset::query()->where('status', AssetStatus::Available)->count();
    }

    protected function countBorrowedAssets(): int
    {
        if (! Schema::hasTable('assets')) {
            return 0;
        }

        return Asset::query()->where('status', AssetStatus::Borrowed)->count();
    }

    protected function countOverdueBorrowings(): int
    {
        if (! Schema::hasTable('asset_borrowings')) {
            return 0;
        }

        return AssetBorrowing::query()->overdue()->count();
    }

    protected function countPendingRequests(): int
    {
        if (! Schema::hasTable('asset_borrowings')) {
            return 0;
        }

        return AssetBorrowing::query()->where('status', BorrowingStatus::Pending)->count();
    }

    protected function availableAssetsUrl(): string
    {
        return FilamentUrl::appendLocaleToUrl(
            AssetResource::getUrl('index', FilamentUrl::withLocale([
                'tab' => 'available',
            ])),
        );
    }

    protected function borrowedAssetsUrl(): string
    {
        return FilamentUrl::appendLocaleToUrl(
            AssetResource::getUrl('index', FilamentUrl::withLocale([
                'tab' => 'borrowed',
            ])),
        );
    }

    protected function allAssetsUrl(): string
    {
        return FilamentUrl::appendLocaleToUrl(
            AssetResource::getUrl('index', FilamentUrl::withLocale()),
        );
    }

    protected function pendingRequestsUrl(): string
    {
        return FilamentUrl::appendLocaleToUrl(PendingBorrowingRequests::getUrl());
    }

    protected function assetViewUrl(int $assetId): string
    {
        return FilamentUrl::appendLocaleToUrl(
            AssetResource::getUrl('view', FilamentUrl::withLocale([
                'record' => $assetId,
            ])),
        );
    }
}
