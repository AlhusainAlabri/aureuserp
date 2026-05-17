<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;

class ViewDocFolder extends ViewRecord
{
    protected static string $resource = DocFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
