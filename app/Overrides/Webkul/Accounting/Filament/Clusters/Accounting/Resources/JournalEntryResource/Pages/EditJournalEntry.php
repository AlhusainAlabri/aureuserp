<?php

namespace Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource\Pages;

use App\Filament\Extensions\AccountingResourceExtensions;

class EditJournalEntry extends CoreEditJournalEntry
{
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);

        return AccountingResourceExtensions::sanitizeJournalLines($data);
    }
}
