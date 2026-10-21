<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\Concerns\PersistsCorrespondenceUploads;

class EditCorrespondence extends EditRecord
{
    use PersistsCorrespondenceUploads;

    protected static string $resource = CorrespondenceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractPendingUploads($data);
    }

    protected function afterSave(): void
    {
        $this->persistPendingUploads();
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            ViewAction::make(),
        ];
    }
}
