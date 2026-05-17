<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;

class EditDocFolder extends EditRecord
{
    protected static string $resource = DocFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
