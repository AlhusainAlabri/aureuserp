<?php

namespace App\Filament\Extensions\AssetResourceExtensions;

use App\Support\FilamentUrl;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\ListAssets as BaseListAssets;

class ListAssets extends BaseListAssets
{
    public function getTableRecordUrlUsing(): ?\Closure
    {
        return fn ($record): string => FilamentUrl::appendLocaleToUrl(
            static::$resource::getUrl('view', ['record' => $record]),
        );
    }
}
