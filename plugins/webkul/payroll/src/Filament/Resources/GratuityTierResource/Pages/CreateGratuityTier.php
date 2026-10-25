<?php

namespace Webkul\Payroll\Filament\Resources\GratuityTierResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Webkul\Payroll\Filament\Resources\GratuityTierResource;

class CreateGratuityTier extends CreateRecord
{
    protected static string $resource = GratuityTierResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())
                    ->mapWithKeys(fn (array $messages, string $key): array => ['data.'.$key => $messages])
                    ->all(),
            );
        }
    }
}
