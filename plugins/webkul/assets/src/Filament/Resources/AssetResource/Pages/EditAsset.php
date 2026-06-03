<?php

namespace Webkul\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Assets\Filament\Resources\AssetResource;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('assets::assets.pages.edit_title', [
            'name' => $this->getRecord()->name,
        ]);
    }
}
