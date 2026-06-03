<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\DocumentArchive\Filament\Concerns\ManagesDocumentRecords;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;

class CreateDocFolder extends CreateRecord
{
    use ManagesDocumentRecords;

    protected static string $resource = DocFolderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['password'], $data['remove_password']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->handleDocumentPassword($this->record, $this->form->getRawState());
    }
}
