<?php

namespace Webkul\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Assets\Filament\Actions\BorrowAction;
use Webkul\Assets\Filament\Actions\ReturnAction;
use Webkul\Assets\Filament\Resources\AssetResource;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    public function getTitle(): string
    {
        return __('assets::assets.pages.view_title', [
            'name' => $this->getRecord()->name,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            BorrowAction::make(),
            ReturnAction::make(),
            EditAction::make(),
        ];
    }
}
