<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\DocumentArchive\Filament\Concerns\ManagesDocumentRecords;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;

class EditDocFile extends EditRecord
{
    use ManagesDocumentRecords;

    protected static string $resource = DocFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->mutateDocumentFormDataBeforeFill($data, $this->record);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mutateDocumentFormData($data);
    }

    protected function afterSave(): void
    {
        $rawState = $this->form->getRawState();

        $this->handleDocumentPassword($this->record, $rawState);
        $this->handleDocumentUpload($this->record, $rawState, false);
    }
}
