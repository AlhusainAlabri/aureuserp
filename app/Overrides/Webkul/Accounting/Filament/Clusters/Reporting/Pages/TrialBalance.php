<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages;

use App\Filament\Concerns\LocalizesAccountingReportingPage;

class TrialBalance extends CoreTrialBalance
{
    use LocalizesAccountingReportingPage;

    protected function getFormSchema(): array
    {
        return $this->getLocalizedReportingFilters('trial-balance');
    }
}
