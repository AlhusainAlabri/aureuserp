<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\DocumentArchive\Filament\Concerns\ManagesDocumentRecords;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;

class EditDocFolder extends EditRecord
{
    use ManagesDocumentRecords;

    protected static string $resource = DocFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['password'], $data['remove_password']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->handleDocumentPassword($this->record, $this->form->getRawState());
    }
}
