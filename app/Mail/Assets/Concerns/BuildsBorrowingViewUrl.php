<?php

namespace App\Mail\Assets\Concerns;

use App\Filament\Assets\Resources\AssetBorrowingResource;
use App\Support\FilamentUrl;
use Webkul\Assets\Models\AssetBorrowing;

trait BuildsBorrowingViewUrl
{
    protected function borrowingViewUrl(AssetBorrowing $borrowing): string
    {
        if (! class_exists(AssetBorrowingResource::class)) {
            return '#';
        }

        try {
            return FilamentUrl::appendLocaleToUrl(
                AssetBorrowingResource::getUrl('view', ['record' => $borrowing->id]),
            );
        } catch (\Throwable) {
            return '#';
        }
    }
}
