<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages;

use App\Filament\Concerns\LocalizesAccountingReportingPage;

class BalanceSheet extends CoreBalanceSheet
{
    use LocalizesAccountingReportingPage;

    protected function getFormSchema(): array
    {
        return $this->getLocalizedReportingFilters('balance-sheet');
    }
}
