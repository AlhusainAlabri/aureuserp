<?php

namespace Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Webkul\Payroll\Filament\Resources\PasiRuleResource;

class EditPasiRule extends EditRecord
{
    protected static string $resource = PasiRuleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (ValidationException $exception) {
            $record->refresh();

            throw ValidationException::withMessages(
                collect($exception->errors())
                    ->mapWithKeys(fn (array $messages, string $key): array => ['data.'.$key => $messages])
                    ->all(),
            );
        }
    }
}
