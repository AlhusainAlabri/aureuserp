<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Webkul\DocumentArchive\Filament\Actions\DownloadDocumentAction;
use Webkul\DocumentArchive\Filament\Actions\ManageDocumentTagsAction;
use Webkul\DocumentArchive\Filament\Actions\PreviewDocumentAction;
use Webkul\DocumentArchive\Filament\Actions\ShareDocumentAction;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;

class ViewDocFile extends ViewRecord
{
    protected static string $resource = DocFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ManageDocumentTagsAction::make(),
            PreviewDocumentAction::make(),
            DownloadDocumentAction::make(),
            ShareDocumentAction::make(),
            EditAction::make(),
        ];
    }
}
