<?php

namespace Webkul\Accounting\Filament\Clusters\Accounting\Resources;

use App\Filament\Concerns\ProvidesAccountingPluralLabel;

class JournalItemResource extends CoreJournalItemResource
{
    use ProvidesAccountingPluralLabel;

    protected static function accountingPluralTranslationKey(): string
    {
        return 'accounting::filament/clusters/accounting/resources/journal-item';
    }
}
