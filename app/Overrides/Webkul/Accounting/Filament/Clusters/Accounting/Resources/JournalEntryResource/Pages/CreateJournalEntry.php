<?php

namespace Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource\Pages;

use App\Filament\Extensions\AccountingResourceExtensions;

class CreateJournalEntry extends CoreCreateJournalEntry
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

        return AccountingResourceExtensions::sanitizeJournalLines($data);
    }
}
