<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\DocumentArchive\Filament\Concerns\ManagesDocumentRecords;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;

class CreateDocFile extends CreateRecord
{
    use ManagesDocumentRecords;

    protected static string $resource = DocFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mutateDocumentFormData($data);
    }

    protected function afterCreate(): void
    {
        $rawState = $this->form->getRawState();

        $this->handleDocumentPassword($this->record, $rawState);
        $this->handleDocumentUpload($this->record, $rawState, true);
    }
}
