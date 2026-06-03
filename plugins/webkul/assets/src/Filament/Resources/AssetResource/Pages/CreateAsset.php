<?php

namespace Webkul\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\Assets\Filament\Resources\AssetResource;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    public function getTitle(): string
    {
        return __('assets::assets.pages.create_title');
    }
}
