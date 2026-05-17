<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;

class ListDocFolders extends ListRecords
{
    protected static string $resource = DocFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
