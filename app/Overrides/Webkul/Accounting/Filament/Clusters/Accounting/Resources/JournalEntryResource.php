<?php

namespace Webkul\Accounting\Filament\Clusters\Accounting\Resources;

use App\Filament\Concerns\LocalizesAccountingResource;

class JournalEntryResource extends CoreJournalEntryResource
{
    use LocalizesAccountingResource;

    protected static function accountingPluralTranslationKey(): string
    {
        return 'accounting::filament/clusters/accounting/resources/journal-entry';
    }
}
